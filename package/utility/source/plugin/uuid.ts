import type { Plugin } from '@unaffected/app'
import uuid from '@unaffected/utility/uuid'

declare module '@unaffected/app' { interface Application { uuid: typeof uuid } }

export const plugin: Plugin = (app) => {
  app.uuid = uuid
}

export default plugin
