import type { Plugin } from '@unaffected/app'
import Cache from '@unaffected/utility/store'

declare module '@unaffected/app' { interface Application { store: Cache<Store> } }

export interface Store {}

export const plugin: Plugin = (app) => {
  app.store = new Cache<Store>()
}

export default plugin
