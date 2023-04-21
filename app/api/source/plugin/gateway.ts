import { type Plugin } from '@unaffected/app'
import gateway from '@unaffected/gateway/plugin'
import http from '@unaffected/gateway/plugin/transport/http'

export const plugin: Plugin = {
  id: 'unaffected:api:gateway',
  install: async (app) => {
    await app.configure(gateway)
    await app.configure(http)
  },
}

export default plugin
