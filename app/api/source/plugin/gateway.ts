import { type Plugin } from '@unaffected/app'
import gateway, { UWS } from '@unaffected/gateway/plugin'
import uuid from '@unaffected/utility/uuid'

export const EVENT = {
  CONNECTED: 'gateway:socket:connected',
  DISCONNECTED: 'gateway:socket:disconnected',
  DRAIN: 'gateway:socket:drain',
  MESSAGE: 'gateway:socket:message',
  REQUEST: 'gateway:http:request',
} as const

export const plugin: Plugin = {
  id: 'unaffected:api:gateway',
  install: async (app) => {
    await app.configure(gateway)

    app
      .gateway
      .ws('/socket', {
        compression: UWS.DEDICATED_COMPRESSOR_3KB,
        idleTimeout: 30,
        maxBackpressure: 1024,
        maxPayloadLength: 512,
        drain: (socket) => { app.channel.publish(EVENT.DRAIN, { socket }) },
        message: (socket, message, is_binary) => { app.channel.publish(EVENT.MESSAGE, { is_binary, message, socket }) },
        close: (socket) => { app.channel.publish(EVENT.DISCONNECTED, socket) },
        open: (socket) => { app.channel.publish(EVENT.CONNECTED, socket) },
      })
      .any('/*', (response, request) => {
        app.channel.publish(EVENT.REQUEST, { id: uuid(), request, response })
      })
  },
}

export { UWS }

export default plugin
