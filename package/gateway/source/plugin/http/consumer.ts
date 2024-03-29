import { UWS } from '@unaffected/gateway'
import type { Application, Plugin } from '@unaffected/app'
import { Gateway } from '@unaffected/gateway'
import * as http from '@unaffected/gateway/utility/http'
import { channel, uuid } from '@unaffected/utility/plugin/index'

declare module '@unaffected/app' {
  interface Application { gateway: Gateway }
}

export type Options = Endpoint | Endpoints

export type Endpoint = {
  endpoint?: string
  event?: string
  method?: Method | Method[]
}

export type Endpoints = Array<Endpoint | Endpoints>

export type Method = typeof METHOD[keyof typeof METHOD]

export const METHOD = {
  ANY: 'any',
  DELETE: 'delete',
  GET: 'get',
  HEAD: 'head',
  OPTIONS: 'options',
  PATCH: 'patch',
  POST: 'post',
  PUT: 'put',
  TRACE: 'trace',
} as const

export const EVENT = {
  REQUEST: 'unaffected:gateway:http:request',
  RESPONSE: (id: string) => `unaffected:gateway:http:response:${id}`,
} as const

export const actions = (app: Application) => ({
  [METHOD.ANY]: app.gateway.any,
  [METHOD.DELETE]: app.gateway.del,
  [METHOD.GET]: app.gateway.get,
  [METHOD.HEAD]: app.gateway.head,
  [METHOD.OPTIONS]: app.gateway.options,
  [METHOD.PATCH]: app.gateway.patch,
  [METHOD.POST]: app.gateway.post,
  [METHOD.PUT]: app.gateway.put,
  [METHOD.TRACE]: app.gateway.trace,
})

export const install: Plugin<Options>['install'] = (app, options = {}) => {
  if (Array.isArray(options)) {
    options.forEach((endpoint) => install.call(app, app, endpoint))

    return
  }

  options.endpoint = options?.endpoint ?? '/*'
  options.method = options?.method ?? 'any'
  options.event = options?.event ?? EVENT.REQUEST

  console.log('endpoint:', options)

  app.gateway.any(options.endpoint, (response: UWS.HttpResponse, request: UWS.HttpRequest) => {
    const allowed = Array.isArray(options.method) ? options.method : [options.method]
    const method = request.getMethod() as Method

    if (!allowed.includes('any') && !allowed.includes(method)) {
      response.writeStatus('405').end('Method not allowed')

      return
    }

    const message: any = {
      id: app.uuid(),
      request: http.parse_request(request),
      response,
    }

    response.writeHeader('Content-Type', message.request.headers?.['content-type'] ?? 'application/json')

    const event = {
      request: options?.event ?? EVENT.REQUEST,
      response: EVENT.RESPONSE(message.id),
    }

    message.request.data = http.get_data(response, (data: any) => {
      message.request.data = data

      app.channel.publish(event.request, message)
    })

    app.channel.subscribe(event.response, (msg) => {
      if (msg.status) {
        response.writeStatus(msg.status)
      }

      if (msg.headers) {
        Object
          .entries(msg.headers)
          .forEach((header: [string, any]) => response.writeHeader(...header))
      }

      if (msg.data || msg.error) {
        response.end(JSON.stringify(msg.error ?? msg.data))
      } else {
        response.end('ok')
      }
    }, { limit: 1 })
  })
}

export const plugin: Plugin<Options> = {
  id: 'unaffected:gateway:http:consumer' as const,
  dependencies: [channel, uuid],
  install,
}

export const { id } = plugin

export default plugin
