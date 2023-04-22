import UWS from 'uWebSockets.js'

export { default, EVENT } from '@unaffected/gateway/plugin/web'

export type Options = UWS.AppOptions
export type Gateway = UWS.TemplatedApp

export const gateway = (options: Options = {}): Gateway => {
  if (options.key_file_name || options.cert_file_name) {
    return UWS.SSLApp(options)
  }

  return UWS.App(options)
}

export { UWS }
