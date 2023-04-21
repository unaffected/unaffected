import { UWS } from '@unaffected/gateway'

export const get_data = (response: UWS.HttpResponse, callback: any) => {
  let buffer: Buffer

  response.onData((stream, is_last) => {
    const chunk = Buffer.from(stream)

    buffer = Buffer.concat([buffer, chunk].filter(Boolean))

    if (is_last) {
      return callback(buffer.byteLength > 0 ? JSON.parse(buffer.toString('utf-8')) : undefined)
    }
  })

  response.onAborted(() => callback('aborted'))
}

export const get_headers = (request: UWS.HttpRequest) => {
  const headers: Record<string, any> = {}

  request.forEach((key, value) => { headers[key] = value })

  return headers
}

export const get_query = (request: UWS.HttpRequest) => {
  return request
    .getQuery()
    .split('&')
    .reduce((acc, param: string) => {
      const [key, value] = param.split('=')

      if (!key) {
        return acc
      }

      acc[key] = value

      return acc
    }, {})
}

export const parse_request = (request: UWS.HttpRequest) => ({
  method: request.getMethod(),
  url: request.getUrl(),
  query: get_query(request),
  headers: get_headers(request),
})
