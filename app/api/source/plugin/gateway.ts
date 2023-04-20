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

export const plugin: Plugin = async (app) => {
  await app.configure(gateway)

  app
    .gateway
    .ws('/socket', {
      compression: UWS.DEDICATED_COMPRESSOR_3KB,
      idleTimeout: 30,
      maxBackpressure: 1024,
      maxPayloadLength: 512,
      drain: (socket) => { app.network.publish(EVENT.DRAIN, { socket }) },
      message: (socket, message, is_binary) => { app.network.publish(EVENT.MESSAGE, { is_binary, message, socket }) },
      close: (socket) => { app.network.publish(EVENT.DISCONNECTED, socket) },
      open: (socket) => { app.network.publish(EVENT.CONNECTED, socket) },
    })
    .any('/*', (response, request) => {
      app.network.publish(EVENT.REQUEST, { id: uuid(), request, response })
    })
}

export { UWS }

export default plugin
