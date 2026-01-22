import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export interface TemplateSelectModalOptions {
  itemId: number
  onInsert?: (content: string) => void
}

export default function (options: TemplateSelectModalOptions): Promise<void> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...options,
      onClose: () => {
        destroyModalApp(app, mountNode)
        resolve()
      },
    })
    app.mount(mountNode)
  })
}

