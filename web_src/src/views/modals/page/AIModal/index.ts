import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export interface AIModalOptions {
  pageId?: number
  itemId?: number
  itemName?: string
  onInsert?: (content: string) => void
}

export default function (options: AIModalOptions): Promise<void> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...options,
      onClose: () => {
        destroyModalApp(app, mountNode)
        resolve()
      }
    })
    app.mount(mountNode)
  })
}

