import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function(options: {
  itemId: number
  onClose?: () => void
}): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      itemId: options.itemId,
      onClose: () => {
        options.onClose?.()
        destroyModalApp(app, mountNode)
        resolve(false)
      },
    })
    app.mount(mountNode)
  })
}
