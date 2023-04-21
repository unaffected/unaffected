import type { Plugin } from '@unaffected/app'
import { EVENT, UWS } from '@unaffected/api/plugin/gateway'

export interface HttpRequest {
  id: string
  request: UWS.HttpRequest
  response: UWS.HttpResponse
}

const get_headers = (request: UWS.HttpRequest) => {
  const headers: Record<string, any> = {}

  request.forEach((key, value) => { headers[key] = value })

  return headers
}

const get_query = (request: UWS.HttpRequest) => {
  return request
    .getQuery()
    .split('&')
    .reduce((acc, param: string) => {
      const [key, value] = param.split('=')

      if (!key) {
        return acc
      }

      acc[key] = value

      return acc
    }, {})
}

export const plugin: Plugin = {
  id: 'unaffected:api:event:http' as const,
  install: (app) => {
    app.channel.on(EVENT.REQUEST, ({ id, response, request }: HttpRequest) => {
      const meta: Record<string, any> = {
        id,
        url: request.getUrl(),
        method: request.getMethod(),
        headers: get_headers(request),
        data: get_query(request),
      }

      response.writeHeader('Content-Type', 'application/json')

      const json = JSON.stringify(meta, undefined, 2)

      response.end(json)
    })
  },
}

export default plugin
