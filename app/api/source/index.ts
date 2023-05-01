import App from '@unaffected/app'
import utility from '@unaffected/utility'
import gateway, { EVENT, UWS } from '@unaffected/gateway'

export const app = new App()

await app.configure([utility, gateway])

const on_http_request = ({ id, request, response }: {
  id: string,
  request: UWS.HttpRequest,
  response: UWS.HttpResponse,
}) => {
  response.end(JSON.stringify({ id, request }))
}


const on_socket_message = ({ socket, message }: {
  socket: UWS.WebSocket<never>,
  message: Buffer,
}) => {
  console.log(JSON.parse(Buffer.from(message).toString('utf-8')))
  socket.send(message)
}

app.channel
  .on(EVENT.HTTP.REQUEST, on_http_request)
  .on(EVENT.WS.MESSAGE, on_socket_message)


app.gateway.listen(9001, (is_listening) => {
  if (is_listening) {
    console.log('server running')
  } else {
    console.log('failed to start server')
  }
})
