import type { Plugin } from '@unaffected/app'
import utility from '@unaffected/utility/plugin/index'

export const plugin: Plugin = {
  id: 'unaffected:api:utility' as const,
  install: async (app) => { await app.configure(utility) },
}

export const { id, install } = plugin

export default plugin
