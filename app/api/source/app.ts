import Application, { type Plugins } from '@unaffected/app'
import gateway from '@unaffected/gateway/plugin'

declare module '@unaffected/app' { interface Application { ready: Promise<boolean> } }

export const app = new Application()

export const plugins: Plugins = [
  gateway,
]

app.ready = app.configure(plugins).then(() => true)

export default app
