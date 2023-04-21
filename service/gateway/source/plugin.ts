import type { Plugin } from '@unaffected/app'
import gateway, { type Gateway, type Options, UWS } from '@unaffected/gateway'

declare module '@unaffected/app' { interface Application { gateway: Gateway } }

export const plugin: Plugin = (app) => {
  app.gateway = gateway()
}

export { UWS }

export default plugin
