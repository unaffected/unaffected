import type { Plugin } from '@unaffected/app'
import type { Gateway } from '@unaffected/gateway'
import { UWS } from '@unaffected/gateway/plugin'
import utility from '@unaffected/utility/plugin/index'

declare module '@unaffected/app' { interface Application { gateway: Gateway } }

export type Options<UserData extends object = Record<string, any>> = UWS.WebSocketBehavior<UserData> & {
  endpoint?: string
}

export const EVENT = {
  CONNECTED: 'unaffected:gateway:ws:connected',
  DISCONNECTED: 'unaffected:gateway:ws:disconnected',
  DRAINED: 'unaffected:gateway:ws:drained',
  MESSAGE: 'unaffected:gateway:ws:message',
} as const

export const plugin: Plugin<Options> = {
  id: 'unaffected:gateway:ws:consumer',
  dependencies: [utility],
  install: (app, options) => {
    app.gateway.ws(options?.endpoint ?? '/*', {
      drain: (socket) => {
        socket.getBufferedAmount()
        app.channel.emit(EVENT.DRAINED, socket)
      },
      open: (socket) => { app.channel.emit(EVENT.CONNECTED, socket) },
      close: (socket) => { app.channel.emit(EVENT.DISCONNECTED, socket) },
      message: (socket, message, is_binary) => {
        app.channel.emit(EVENT.MESSAGE, { is_binary, message, socket })
      },
      ...options,
    })
  },
}

export default plugin
