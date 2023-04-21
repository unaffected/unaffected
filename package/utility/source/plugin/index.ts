import type { Plugin, Plugins } from '@unaffected/app'

import channel from '@unaffected/utility/plugin/channel'
import guard from '@unaffected/utility/plugin/guard'
import store from '@unaffected/utility/plugin/store'
import timer from '@unaffected/utility/plugin/timer'
import uuid from '@unaffected/utility/plugin/uuid'

export const plugins: Plugins = [
  channel,
  guard,
  store,
  timer,
  uuid,
]

export const plugin: Plugin = {
  id: 'unaffected:utility' as const,
  install: async (app) => {
    await app.configure(plugins)
  },
}

export {
  channel,
  guard,
  store,
  timer,
  uuid
}

export default plugin
