import * as guard from './guard'

describe('utility: guard', () => {
  test('creating a policy', async () => {
    const policy = guard.check(() => true)

    expect(typeof policy).toBe('function')
  })

  test('authorizing a simple policy', async () => {
    const policy = guard.check(() => true)

    expect(await policy({})).toBe(true)
  })

  test('authorizing a dynamic policy', async () => {
    const policy = guard.check(({ authorize }) => authorize)

    expect(await policy({ authorize: true })).toBe(true)
    expect(await policy({ authorize: false })).toBe(false)
  })

  describe('authorizing multiple policies', () => {
    test('ensuring all policies are satisfied', async () => {
      const policy = guard.every([
        () => true,
        ({ authorize }) => authorize,
        ({ path }) => path === '/foo/bar',
      ])

      const pass = { authorize: true, path: '/foo/bar' }
      const fail = { authorize: true, path: '/bar/baz' }

      expect(await policy(pass)).toBe(true)
      expect(await policy(fail)).toBe(false)

      const single = guard.every(() => true)

      expect(await single({})).toBe(true)
    })

    test('ensuring at least one policy is satisified', async () => {
      const policy = guard.some([
        () => false,
        ({ authorize }) => authorize,
        ({ path }) => path === '/foo/bar',
      ])

      const pass = { authorize: false, path: '/foo/bar' }
      const fail = { authorize: false, path: '/bar/baz' }

      expect(await policy(pass)).toBe(true)
      expect(await policy(fail)).toBe(false)


      const single = guard.some(() => true)

      expect(await single({})).toBe(true)
    })
  })
})
