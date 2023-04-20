import app from '@unaffected/api/app'

const start = async () => {
  await app.ready

  app.gateway.get('*', (res) => res.end('Hello, world.'))

  app.gateway.listen(8080, (token) => {
    if (token) {
      console.info('listening on port 8080')
    } else {
      console.error('failed to start gateway')
    }
  })

  return app
}

start().then(() => { console.log('started') })
