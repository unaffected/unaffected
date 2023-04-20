import app from '@unaffected/api/app'

const PORT = 9001 as const

app.gateway.listen(PORT, (token) => {
  if (token) {
    console.info('listening on port:', PORT)
  } else {
    console.error('failed to start gateway')
  }
})
