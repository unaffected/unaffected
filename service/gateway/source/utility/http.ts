import { UWS } from '@unaffected/gateway'

export const get_data = (request: UWS.HttpRequest) => {
  const data = request.getQuery()

  try {
    return JSON.parse(data)
  } catch (e) {
    return data
  }
}

export const get_headers = (request: UWS.HttpRequest) => {
  const headers: Record<string, any> = {}

  request.forEach((key, value) => { headers[key] = value })

  return headers
}

export const get_meta = (request: UWS.HttpRequest) => ({
  method: request.getMethod(),
  url: request.getUrl(),
  query: get_query(request),
  headers: get_headers(request),
  data: get_data(request),
})

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
