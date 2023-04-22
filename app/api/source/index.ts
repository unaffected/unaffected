import App from '@unaffected/app' // core application module
import utility from '@unaffected/utility/plugin/index' // recommended utility plugins,
import gateway, { EVENT, UWS } from '@unaffected/gateway' // gateway (both http/ws by default)

// create a new application
export const app = new App()

// configure some plugins
await app.configure([utility, gateway])

// define a handle for http events
const on_http_request = ({ id, request, response }: {
  id: string,
  request: UWS.HttpRequest,
  response: UWS.HttpResponse,
}) => {
  // echo the request data back to the requestor
  response.end(JSON.stringify({ id, request }))
}

// define a socket message handler
const on_socket_message = ({ socket, message }: {
  socket: UWS.WebSocket<never>,
  message: Buffer,
}) => {
  console.log(JSON.parse(Buffer.from(message).toString('utf-8')))
  socket.send(message)
}

// register the event handlers
app.channel
  .on(EVENT.HTTP.REQUEST, on_http_request)
  .on(EVENT.WS.MESSAGE, on_socket_message)

// start the server on port 9001
app.gateway.listen(9001 , (is_listening) => {
  if (is_listening) {
    console.log('server running')
  } else {
    console.log('failed to start server')
  }
})

export default {}
