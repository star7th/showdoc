import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export interface AttachmentModalOptions {
  itemId?: number
  pageId?: number
  manage?: boolean
}

export default function (options: AttachmentModalOptions): Promise<void> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...options,
      onClose: () => {
        resolve()
        destroyModalApp(app, mountNode)
      }
    })
    app.mount(mountNode)
  })
}

