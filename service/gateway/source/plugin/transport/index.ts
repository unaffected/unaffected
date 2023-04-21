import type { Plugin, Plugins } from '@unaffected/app'
import http from '@unaffected/gateway/plugin/transport/http'

export const plugins: Plugins = [
  http,
]

export const plugin: Plugin = {
  id: 'unaffected:gateway:transport' as const,
  install: async (app) => {
    await app.configure(plugins)
  },
}

export const { id, install } = plugin

export { http }

export default plugin
