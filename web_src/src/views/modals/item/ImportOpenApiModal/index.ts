import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      onClose: (result: boolean) => {
        resolve(result)
        destroyModalApp(app, mountNode)
      }
    })
    app.mount(mountNode)
  })
}

