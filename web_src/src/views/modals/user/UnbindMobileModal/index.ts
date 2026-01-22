import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (): Promise<void> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      onClose: () => {
        resolve()
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}

