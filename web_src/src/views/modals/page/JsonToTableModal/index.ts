import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (options: {
  onClose?: () => void
  onInsert?: (table: string) => void
}): Promise<string> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      onClose: () => {
        options.onClose?.()
        destroyModalApp(app, mountNode)
        resolve('')
      },
      onInsert: (table: string) => {
        options.onInsert?.(table)
        destroyModalApp(app, mountNode)
        resolve(table)
      },
    })
    app.mount(mountNode)
  })
}

