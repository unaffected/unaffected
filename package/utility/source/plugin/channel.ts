import type { Plugin } from '@unaffected/app'
import Channel from '@unaffected/utility/channel'

declare module '@unaffected/app' { interface Application { channel: Channel } }

export const plugin: Plugin = {
  id: 'unaffected:utility:channel' as const,
  install: (app) => {
    app.channel = new Channel()
  },
}

export default plugin
