import type { Plugin, Plugins } from '@unaffected/app'
import event from '@unaffected/api/event/index'
import gateway from '@unaffected/api/plugin/gateway'
import utility from '@unaffected/api/plugin/utility'

declare module '@unaffected/app' { interface Application { start: Promise<boolean> } }

export const plugins: Plugins = [
  utility,
  event,
  gateway,
]

export const plugin: Plugin = {
  id: 'unaffected:api' as const,
  install: async (app) => { await app.configure(plugins) },
}

export default plugins
