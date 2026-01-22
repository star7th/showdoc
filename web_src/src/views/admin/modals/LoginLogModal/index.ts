import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface LoginLogModalParams {
  uid: number
}

export default function (params: LoginLogModalParams): Promise<void> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...params,
      onClose: () => {
        resolve()
        destroyModalApp(app, mountNode)
      }
    })
    app.mount(mountNode)
  })
}

