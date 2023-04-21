import type { Plugin } from '@unaffected/app'
import Cache from '@unaffected/utility/store'

declare module '@unaffected/app' { interface Application { store: Cache<Store> } }

export interface Store {}

export const plugin: Plugin = {
  id: 'unaffected:utility:store' as const,
  install: (app) => {
    app.store = new Cache<Store>()
  },
}

export const { id, install } = plugin

export default plugin
