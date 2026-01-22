import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (itemId: string | number): Promise<void> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      itemId,
      onClose: () => {
        resolve()
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}

