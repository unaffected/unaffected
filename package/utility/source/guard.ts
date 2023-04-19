export type Context = Record<string, any>
export type Policy<T = Context> = (context: T) => boolean | Promise<boolean>
export type Policies<T = Context> = Policy<T> | Policy<T>[]

export const check = <T = Context> (policy: Policy<T>): Policy<T> => {
  return async (context: T) => {
    return Promise
      .resolve(policy)
      .then(p => p(context))
      .then(Boolean)
      .catch(() => false)
  }
}

export const many = <T = Context> (policy: Policies<T>) => {
  const policies = Array.isArray(policy) ? policy : [policy]
  const checks = policies.map(check)

  return async (context: T) => Promise.all(checks.map(check => check(context)))
}

export const every = <T = Context> (policy: Policies<T>): Policy<T> => {
  const check = many(policy)

  return (context: T) => check(context)
    .then((checks) => checks.every(Boolean))
    .catch(() => false)
}

export const some = <T = Context> (policy: Policies<T>): Policy<T> => {
  const check = many(policy)

  return (context: T) => check(context)
    .then((checks) => checks.some(Boolean))
    .catch(() => false)
}
