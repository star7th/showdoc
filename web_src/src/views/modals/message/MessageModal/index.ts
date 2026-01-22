import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (initialTab?: string): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      initialTab,
      onClose: (res = false) => {
        resolve(res)
        destroyModalApp(app, mountNode)
      }
    })
    app.mount(mountNode)
  })
}

