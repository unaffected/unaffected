export type Plugin = (this: Application, app: Application) => void | Promise<void>
export type Plugins = Array<Plugins | Plugin>

const uid = () => ('000' + ((Math.random() * 46656) | 0).toString()).slice(-3)

export class Application {
  public readonly id: string

  constructor() {
    this.id = `${Date.now()}:${uid()}`
  }

  async configure(plugins: Plugin | Plugins) {
    if (!Array.isArray(plugins)) {
      await plugins.call(this, this)

      return this
    }

    const promises = plugins.map((plugin) => this.configure(plugin))

    await Promise.all(promises)

    return this
  }
}

export default Application
