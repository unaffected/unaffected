import App from '@unaffected/app'
import http, { EVENT } from '@unaffected/gateway/plugin/transport/http'

interface Services {
  math: typeof math
}

const math = {
  add: (x: number, y: number) => x + y,
  subtract: (x: number, y: number) => x - y,
}

export const app = new App<Services>()

app.service('math', math)

await app.configure(http)

// /math/add -> math/add -> [math, add]

app.channel.on(EVENT.REQUEST, ({ id, request, response }) => {
  console.log('received request:', id)

  const [name, action] = request.url.replace('/', '').split('/')

  console.log(name, action)

  // response.end(JSON.stringify([name, action]))

  const service = app.service(name)
  const result = service?.[action]?.(...request.data)

  response.end(JSON.stringify(result))
})

export default app
