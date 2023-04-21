import type { Plugin } from '@unaffected/app'
import * as guard from '@unaffected/utility/guard'

declare module '@unaffected/app' { interface Application { guard: guard.Guard } }

export const plugin: Plugin = {
  id: 'unaffected:utility:guard' as const,
  install: (app) => {
    app.guard = guard
  },
}

export const { id, install } = plugin

export default plugin
