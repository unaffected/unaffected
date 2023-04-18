import type { Plugin } from '@unaffected/app'
import timer from '.'

declare module '@unaffected/app' { interface Application { timer: typeof timer } }

export const plugin: Plugin = (app) => {
  app.timer = timer
}

export default plugin
