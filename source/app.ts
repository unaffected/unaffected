export type Context<T extends Record<string, any> = Record<string, any>> = T
export type Plugin = (this: Application, app: Application) => void | Promise<void>
export type Plugins = Array<Plugins | Plugin>

export class Application {
  public readonly created_at: number

  constructor() {
    this.created_at = Date.now()
  }

  async configure(plugin: Plugin | Plugins) {
    if (!Array.isArray(plugin)) {
      await plugin.call(this, this)

      return this
    }

    const plugins = plugin.map(this.configure.bind(this))

    await Promise.all(plugins)

    return this
  }
}

export default Application
