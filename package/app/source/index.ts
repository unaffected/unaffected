export type Plugin = { id: string, install: (this: Application, app: Application) => void | Promise<void> }
export type Plugins = Array<Plugins | Plugin>

const uid = () => ('000' + ((Math.random() * 46656) | 0).toString()).slice(-3)

export class Application {
  public readonly id: string

  #plugins: string[]

  constructor() {
    this.id = `${Date.now()}:${uid()}`
    this.#plugins = []
  }

  get plugins() {
    return this.#plugins
  }

  async configure(plugins: Plugin | Plugins) {
    if (!Array.isArray(plugins)) {
      await this.install(plugins)

      return this
    }

    const promises = plugins.map((plugin: Plugin) => this.configure(plugin))

    await Promise.all(promises)

    return this
  }

  is_installed(plugin: string | Plugin) {
    return this.#plugins.includes(typeof plugin === 'string' ? plugin : plugin.id)
  }

  private async install(plugin: Plugin) {
    if (this.is_installed(plugin)) {
      return this
    }

    this.#plugins.push(plugin.id)

    await plugin.install.call(this, this)

    return this
  }
}

export default Application
