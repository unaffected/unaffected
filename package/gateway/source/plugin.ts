import type { Plugin } from '@unaffected/app'
import { gateway, type Gateway, type Options, UWS } from '@unaffected/gateway'

declare module '@unaffected/app' { interface Application { gateway: Gateway } }

export const plugin: Plugin<Options> = {
  id: 'unaffected:gateway' as const,
  install: async (app, options = {}) => {
    app.gateway = gateway(options)
  },
}

export { UWS }

export default plugin
