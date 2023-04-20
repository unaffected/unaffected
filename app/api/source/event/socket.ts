import type { Plugin } from '@unaffected/app'
import { EVENT } from '@unaffected/api/plugin/gateway'

export const plugin: Plugin = (app) => {
  app.network.subscribe(EVENT.CONNECTED, ({}) => {})
  app.network.subscribe(EVENT.DISCONNECTED, ({}) => {})
  app.network.subscribe(EVENT.DRAIN, ({}) => {})
  app.network.subscribe(EVENT.MESSAGE, ({}) => {})
}

export default plugin
