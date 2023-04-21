import Application from '@unaffected/app'
import http, { EVENT } from '@unaffected/gateway/plugin/transport/http'

export const app = new Application()

await app.configure(http)

app.channel.on(EVENT.REQUEST, ({ id, request, response }) => {
  response.end(JSON.stringify({ id, request }))
})

export default app
