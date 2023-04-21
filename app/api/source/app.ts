import Application from '@unaffected/app'
import gateway from '@unaffected/api/plugin/gateway'

declare module '@unaffected/app' { interface Application { ready: Promise<boolean> } }

export const app = new Application()

await app.configure(gateway)

app.channel.on('unaffected:gateway:http:request', ({ id, response }) => {
  console.log('event:', id)
  response.end(`event: ${id}`)
})

export default app
