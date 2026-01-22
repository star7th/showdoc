import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export interface SaveTemplateModalOptions {
  content: string
  onSuccess?: () => void
}

export default function (options: SaveTemplateModalOptions): Promise<void> {
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

