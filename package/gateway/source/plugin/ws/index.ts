import type { Plugin } from '@unaffected/app'
import { type Gateway } from '@unaffected/gateway'
import consumer, { type EVENT } from '@unaffected/gateway/plugin/ws/consumer'

declare module '@unaffected/app' { interface Application { gateway: Gateway } }

export const plugin: Plugin = {
  id: 'unaffected:gateway:ws' as const,
  install: async (app) => {
    await app.configure(consumer)
  },
}

export type { EVENT }

export default plugin
