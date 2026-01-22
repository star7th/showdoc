import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (callback?: (qrscene: string) => void): void {
  const { app, mountNode } = createModalApp(Component, {
    callback: (qrscene: string) => {
      if (callback) {
        callback(qrscene)
      }
      destroyModalApp(app, mountNode)
    },
    onClose: () => {
      destroyModalApp(app, mountNode)
    },
  })
  app.mount(mountNode)
}

