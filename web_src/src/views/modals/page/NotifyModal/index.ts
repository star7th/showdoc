import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export interface NotifyModalOptions {
  itemId: number | string
  pageId: number | string
  onClose?: () => void
  onConfirm?: (content: string) => void
}

export default function (options: NotifyModalOptions): Promise<string> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      itemId: options.itemId,
      pageId: options.pageId,
      onClose: () => {
        options.onClose?.()
        destroyModalApp(app, mountNode)
        resolve('')
      },
      onConfirm: (content: string) => {
        options.onConfirm?.(content)
        destroyModalApp(app, mountNode)
        resolve(content)
      },
    })
    app.mount(mountNode)
  })
}
