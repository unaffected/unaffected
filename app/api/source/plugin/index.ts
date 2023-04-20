import type { Plugin, Plugins } from '@unaffected/app'
import event from '@unaffected/api/event/index'
import gateway from '@unaffected/api/plugin/gateway'
import store from '@unaffected/api/plugin/store'

declare module '@unaffected/app' { interface Application { start: Promise<boolean> } }

export const plugins: Plugins = [
  store,
  event,
  gateway,
]

export const plugin: Plugin = (app) => { app.configure(plugins) }

export default plugins
