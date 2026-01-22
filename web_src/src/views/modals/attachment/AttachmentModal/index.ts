import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface AttachmentModalOptions {
  itemId?: number
  pageId?: number
  manage?: boolean
}

export default function (options?: AttachmentModalOptions): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...options,
      onClose: (result: boolean) => {
        resolve(result)
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}

