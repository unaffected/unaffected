import http from '@unaffected/api/event/http'
import socket from '@unaffected/api/event/socket'
import type { Plugin, Plugins } from '@unaffected/app'
import Channel from '@unaffected/utility/channel'

declare module '@unaffected/app' { interface Application { network: Channel } }

export const plugins: Plugins = [
  http,
  socket,
]

export const plugin: Plugin = (app) => {
  app.network = new Channel()

  app.configure(plugins)
}

export default plugin
