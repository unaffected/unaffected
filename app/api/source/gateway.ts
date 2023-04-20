import { type Plugin } from '@unaffected/app'
import gateway from '@unaffected/gateway/plugin'

export const plugin: Plugin = async (app) => {
  await app.configure(gateway)

  app
    .gateway
    .any('*', (res, req) => res.end(`url: ${req.getUrl()}`))
    .ws('*', {
      drain: (_socket) => {},
      message: (_socket) => {},
      close: (_socket) => {},
      open: (_socket) => {},
    })
}
