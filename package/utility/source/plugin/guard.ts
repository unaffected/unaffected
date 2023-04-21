import type { Plugin } from '@unaffected/app'
import * as guard from '@unaffected/utility/guard'

declare module '@unaffected/app' { interface Application { guard: guard.Guard } }

export const plugin: Plugin = (app) => {
  app.guard = guard
}

export default plugin
