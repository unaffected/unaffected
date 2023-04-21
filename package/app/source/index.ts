export type Plugin<Options = unknown> = {
  id: string,
  dependencies?: Plugin[]
  install: (this: Application, app: Application, options?: Options) => void | Promise<void>,
  options?: Options
}

export type Plugins = Array<Plugins | Plugin>

export interface Options<S = Services> {
  name?: string
  plugins?: Plugins[]
  services?: S
}

export interface Services {}

const uid = () => ('000' + ((Math.random() * 46656) | 0).toString()).slice(-3)

export class Application <AppServices = Services> {
  public readonly id: string
  public readonly name: string

  #plugins: string[]
  #ready: boolean
  #services: AppServices

  constructor(options: Options<AppServices> = {}) {
    this.name = options.name ?? Date.now().toString()
    this.id = `${this.name}:${uid()}`

    this.#ready = false
    this.#services = (options.services ?? {} as AppServices) satisfies AppServices
    this.#plugins = []

    this.setup(options)
  }

  get ready() { return this.#ready }
  get services() { return Object.keys(this.#services) }
  get plugins() { return this.#plugins }

  static extend<Provider>(provider?: Provider): Application<Provider> {
    if (provider instanceof Application) {
      return provider
    }

    const app = new Application<Provider>()

    for (const key in provider) {
      (<any>app)[key] = provider[key]
    }

    return app as Application<Provider>
  }

  public async configure(plugins: Plugin | Plugins) {
    if (!Array.isArray(plugins)) {
      await this.install(plugins)

      return this
    }

    const promises = plugins.map((plugin: Plugin) => this.configure(plugin))

    await Promise.all(promises)

    return this
  }

  public is_installed(plugin: string | Plugin) {
    return this.#plugins.includes(typeof plugin === 'string' ? plugin : plugin.id)
  }

  public async install(plugin: Plugin) {
    if (this.is_installed(plugin)) {
      return this
    }

    this.#plugins.push(plugin.id)

    if (plugin.dependencies?.length > 0) {
      await this.configure(plugin.dependencies)
    }

    await plugin.install.call(this, this, plugin.options)

    return this
  }

  public service<
    Service extends keyof AppServices & string,
    Provider extends Application | object
  >(service: Service, provider?: Provider): AppServices[Service] {
    const services = Object.keys(this.#services)

    if (!provider) {
      return <AppServices[Service]> this.#services[service]
    }

    const app = Application.extend(provider)

    this.#services[service as keyof typeof services] = <Application<Provider>> app

    return app as AppServices[Service]
  }

  private async setup(options: Options = {}) {
    await this.configure(options.plugins ?? [])

    this.#ready = true
  }
}

export default Application
