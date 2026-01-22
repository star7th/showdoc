import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (): Promise<any> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      onClose: (result: boolean, data: any) => {
        resolve(data)
        destroyModalApp(app, mountNode)
      }
    })
    app.mount(mountNode)
  })
}

