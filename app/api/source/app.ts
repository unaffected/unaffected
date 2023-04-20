import Application from '@unaffected/app'
import plugins from '@unaffected/api/plugin/index'

declare module '@unaffected/app' { interface Application { ready: Promise<boolean> } }

export const app = new Application()

await app.configure(plugins)

export default app
