import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (options: {
  onClose?: () => void
  onInsert?: (json: string) => void
}): Promise<string> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      onClose: () => {
        options.onClose?.()
        destroyModalApp(app, mountNode)
        resolve('')
      },
      onInsert: (json: string) => {
        options.onInsert?.(json)
        destroyModalApp(app, mountNode)
        resolve(json)
      },
    })
    app.mount(mountNode)
  })
}

