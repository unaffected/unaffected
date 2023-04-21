import type { Plugin, Plugins } from '@unaffected/app'
import gateway from '@unaffected/api/plugin/gateway'
import utility from '@unaffected/utility/plugin/index'

declare module '@unaffected/app' { interface Application { start: Promise<boolean> } }

export const plugins: Plugins = [
  utility,
  gateway,
]

export const plugin: Plugin = {
  id: 'unaffected:api' as const,
  install: async (app) => { await app.configure(plugins) },
}

export default plugins
