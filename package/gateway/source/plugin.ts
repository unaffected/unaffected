import type { Plugin } from '@unaffected/app'
import gateway, { type Gateway } from './index'

declare module '@unaffected/app' { interface Application { gateway: Gateway } }

export const plugin: Plugin = (app) => {
  app.gateway = gateway()
}

export default plugin
