import { HydrationScript, renderToString, renderToStringAsync, renderToStream } from 'solid-js/web'

export const App = (props: any) => {
  return (
    <html lang="en">
      <head>
        <title>🔥 Solid SSR 🔥</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <HydrationScript />
      </head>
      <body>{props.children}</body>
    </html>
  )
}

export const render = (input: any) => renderToString(() => App(input))
export const render_async = async (input: any) => renderToStringAsync(() => App(input))
export const render_stream = (input: any) => renderToStream(() => App(input))

export default {}
