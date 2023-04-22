import type { Plugin } from '@unaffected/app'
import { type Gateway } from '@unaffected/gateway'
import consumer from '@unaffected/gateway/plugin/http/consumer'

declare module '@unaffected/app' { interface Application { gateway: Gateway } }

export const plugin: Plugin = {
  id: 'unaffected:gateway:http' as const,
  install: async (app) => {
    await app.configure(consumer)
  },
}

export default plugin
