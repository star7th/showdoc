import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface ImportFileModalProps {
  itemId?: number
}

export default function (props?: ImportFileModalProps): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...props,
      onClose: (result: boolean) => {
        resolve(result)
        destroyModalApp(app, mountNode)
      }
    })
    app.mount(mountNode)
  })
}

