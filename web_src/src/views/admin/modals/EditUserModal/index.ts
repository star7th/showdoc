import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface EditUserModalParams {
  user?: any
}

export default function (params?: EditUserModalParams): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...params,
      onClose: (result: boolean) => {
        resolve(result)
        destroyModalApp(app, mountNode)
      }
    })
    app.mount(mountNode)
  })
}

