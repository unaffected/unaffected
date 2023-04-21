import type { Plugin } from '@unaffected/app'
import * as timer from '@unaffected/utility/timer'

declare module '@unaffected/app' { interface Application { timer: typeof timer } }

export const plugin: Plugin = {
  id: 'unaffected:utility:timer' as const,
  install: (app) => {
    app.timer = timer
  },
}

export const { id, install } = plugin

export default plugin
