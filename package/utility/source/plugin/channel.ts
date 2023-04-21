import type { Plugin } from '@unaffected/app'
import Channel from '@unaffected/utility/channel'

declare module '@unaffected/app' { interface Application { channel: Channel } }

export const plugin: Plugin = (app) => {
  app.channel = new Channel()
}

export default plugin
