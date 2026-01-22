import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (options: {
  pageId?: number
  itemId?: number
  onClose?: () => void
}): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      pageId: options.pageId || 0,
      itemId: options.itemId || 0,
      onClose: () => {
        options.onClose?.()
        destroyModalApp(app, mountNode)
        resolve(false)
      },
    })
    app.mount(mountNode)
  })
}
