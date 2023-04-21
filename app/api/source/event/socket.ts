import type { Plugin } from '@unaffected/app'
import { EVENT } from '@unaffected/api/plugin/gateway'

export const plugin: Plugin = {
  id: 'unaffected:api:event:socket' as const,
  install: (app) => {
    app.channel.subscribe(EVENT.CONNECTED, ({}) => {})
    app.channel.subscribe(EVENT.DISCONNECTED, ({}) => {})
    app.channel.subscribe(EVENT.DRAIN, ({}) => {})
    app.channel.subscribe(EVENT.MESSAGE, ({}) => {})
  },
}

export default plugin
