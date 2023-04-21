import type { Plugin } from '@unaffected/app'
import gateway, { type Gateway, UWS } from '@unaffected/gateway'

declare module '@unaffected/app' { interface Application { gateway: Gateway } }

export const plugin: Plugin = {
  id: 'unaffected:gateway' as const,
  install: (app) => {
    app.gateway = gateway()
  },
}

export const { id, install } = plugin

export { UWS }

export default plugin
