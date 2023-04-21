import type { Plugin } from '@unaffected/app'
import uuid from '@unaffected/utility/uuid'

declare module '@unaffected/app' { interface Application { uuid: typeof uuid } }

export const plugin: Plugin = {
  id: 'unaffected:utility:uuid' as const,
  install: (app) => {
    app.uuid = uuid
  },
}

export const { id, install } = plugin

export default plugin
