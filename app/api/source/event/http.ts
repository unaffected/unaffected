import type { Plugin } from '@unaffected/app'
import { EVENT, UWS } from '@unaffected/api/plugin/gateway'

export interface HttpRequest {
  id: string
  request: UWS.HttpRequest
  response: UWS.HttpResponse
}

export const plugin: Plugin = (app) => {
  app.network.on(EVENT.REQUEST, ({ id, response, request }: HttpRequest) => {
    if (request.getUrl() === '/foo') {
      response.write('foo foo foo')
    }

    response.writeStatus('200 OK').end(`Hello from event, ${id}.`)
  })
}

export default plugin
