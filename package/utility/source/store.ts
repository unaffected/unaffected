import { get, set, mergeWith, omit, pick } from '@unaffected/utility'

export type State<T extends Record<string, any> = Record<string, any>> = T

export interface Timeout<T extends string> {
  key: T
  expiration: number
  created_at: number
  expires_at: number
  timer: NodeJS.Timer | number
  cancel: () => void
}

export type Timeouts<T extends string> = Map<T, Timeout<T>>

export type Timer<T extends string> = Omit<Timeout<T>, 'timer'> & { remaining: number }
export type Timers<T extends string> = Partial<Record<T, Timer<T>>>

export class Store<T extends State> {
  private readonly timeouts: Timeouts<string>
  private readonly initial: Partial<T>
  private state: Partial<T>

  constructor(state: Partial<T> = {}) {
    this.timeouts = new Map()
    this.initial = { ...state }
    this.state = state
  }

  /**
   * Retrieve all the key-value pairs in
   * the store as a collection of tuples.
   */
  get entries() {
    return Object.entries(this.state)
  }

  /**
   * Retrieve all the keys in from the store.
   */
  get keys() {
    return Object.keys(this.state)
  }

  /**
   * Retrieve the store's active expiration timers.
   */
  get timers(): Timers<string> {
    return Array
      .from(this.timeouts.values())
      .reduce((acc, { timer, ...timeout }) => ({
        ...acc,
        [timeout.key]: {
          ...timeout,
          remaining: timeout.expiration - (Date.now() - timeout.created_at),
        },
      }), {})
  }

  get values() {
    return Object.values(this.state)
  }

  equals(key: string | string[], value?: any): boolean {
    if (Array.isArray(key)) {
      return key.every((k) => this.equals(k, value))
    }

    return this.get(key) === value
  }

  /**
   * Set a duration-based expiration for the given key(s).
   */
  expire(key: string | string[], expiration: number) {
    if (Array.isArray(key)) {
      key.forEach((k) => this.expire(k, expiration))

      return this
    }

    const created_at = Date.now()
    const timer = setTimeout(() => { this.remove(key) }, expiration)

    const timeout: Timeout<string> = {
      key,
      expiration,
      timer,
      created_at,
      expires_at: created_at + expiration,
      cancel: () => clearTimeout(timer),
    }

    this.keep(key)

    this.timeouts.set(key, timeout)

    return this
  }

  /**
   * Set a date-based expiration for the given key(s).
   */
  expire_at(key: string | string[], expire_at: number | Date) {
    const time = typeof expire_at === 'number' ? expire_at : expire_at.getTime()
    const expiration = time - Date.now()

    if (expiration <= 0) {
      return this.remove(key)
    }

    return this.expire(key, expiration)
  }

  /**
   * Determine if a non-undefined value exists at the given key(s).
   */
  exists(key: string | string[]): boolean {
    if (Array.isArray(key)) {
      return key.every(this.exists.bind(this))
    }

    return [
      this.keys.includes(key),
      this.type(key) !== 'undefined',
    ].every(Boolean)
  }

  /**
   * Retrieve the value at the given key.
   * If the value is undefined the fallback will be returned instead.
   */
  get(key?: string, fallback?: any): State<T> | any {
    if (key) {
      return get(this.state, key) ?? fallback
    }

    return this.state
  }

  /**
   * Determine if the value of a specific key is the given type.
   */
  is(key: string | string[], type: any): boolean {
    if (Array.isArray(key)) {
      return key.every((k) => this.is(k, type))
    }

    if (['boolean', 'object', 'number', 'string', 'undefined'].includes(type)) {
      return this.type(key) === type
    }

    const value = this.get(key) as any

    if (type === 'array') {
      return Array.isArray(value)
    }

    if (type === 'date') {
      return value instanceof Date
    }

    return value === type
  }

  /**
   * Determine if the value at the given key is an array.
   * @param key string
   * @returns {boolean}
   */
  is_array(key: string | string[]) {
    return this.is(key, 'array')
  }

  /**
   * Determine if the value at the given key is a boolean.
   * @param key string
   * @returns {boolean}
   */
  is_boolean(key: string | string[]) {
    return this.is(key, 'boolean')
  }

  /**
   * Determine if the value at the given key is a date.
   */
  is_date(key: string | string[]) {
    return this.is(key, 'date')
  }

  /**
   * Determine if the value at the given key is an object.
   */
  is_object(key: string | string[]) {
    return this.is(key, 'object')
  }

  /**
   * Determine if the value at the given key is a number.
   */
  is_number(key: string | string[]) {
    return this.is(key, 'number')
  }

  /**
   * Determine if the value at the given key is a string.
   */
  is_string(key: string | string[]) {
    return this.is(key, 'string')
  }

  /**
   * Determine if the value at the given key is undefined.
   */
  is_undefined(key: string | string[]) {
    return this.is(key, 'undefined')
  }

  /**
   * Cancel any expiration timeouts for the given key(s).
   */
  keep(key: string | string[]) {
    if (Array.isArray(key)) {
      key.forEach(this.keep.bind(this))

      return this
    }

    const timeout = this.timeouts.get(key)

    if (timeout) {
      timeout.cancel()
    }

    return this
  }

  /**
   * Recursively merge the given state with the current state.
   */
  merge(state: Partial<T>) {
    this.state = mergeWith(this.state, state, (existing, mutation) => {
      if (Array.isArray(existing)) {
        return [...existing, ...mutation]
      }

      return mutation
    })

    return this
  }

  /**
   * Retrieve an object of key-value pairs for the given keys.
   */
  pick(key: string | string[] = []) {
    return pick(this.state, key as keyof State<T>)
  }

  /**
   * Refresh the expiration timer for the given key(s).
   */
  refresh(key: string | string[]) {
    if (Array.isArray(key)) {
      key.forEach(this.refresh.bind(this))

      return this
    }

    const timeout = this.timeouts.get(key)

    if (timeout) {
      timeout.cancel()
      this.expire(timeout.key, timeout.expiration)
    }

    return this
  }

  /**
   * Remove the given key(s).
   */
  remove(key: string | string[]) {
    this.state = omit(this.state, key) as Partial<T>

    return this
  }

  /**
   * Replace the current state.
   */
  replace(state: Partial<T>) {
    this.state = state

    return this
  }

  /**
   * Reset the state of the given key(s).
   * Reset the entire store if no keys are provided.
   */
  reset(key?: string | string[]) {
    if (Array.isArray(key)) {
      key.forEach(this.reset.bind(this))

      return this
    }

    if (!key) {
      this.state = this.initial

      return this
    }

    this.set(key, get(this.initial, key))

    return this
  }

  /**
   * Set the value of the given key.
   * The key will expire automatically if an expiration is provided.
   */
  set(key: string, value?: any, expiration?: number) {
    set(this.state, key, value)

    if (expiration) {
      this.expire(key, expiration)
    }

    return this
  }

  /**
   * Retrieve the value for the given key(s), and
   * remove any retrieved keys from the store.
   */
  take(key: string | string[], fallback?: any) {
    const value = Array.isArray(key) ? this.pick(key) : this.get(key)

    this.remove(key)

    return value
  }

  /**
   * Retrieve the expiration timer for the given key.
   */
  timer(key: string) {
    return this.timers[key]
  }

  /**
   * Determine the type of the value stored at the given key.
   */
  type(key: string) {
    return typeof this.get(key)
  }
}

export default Store
