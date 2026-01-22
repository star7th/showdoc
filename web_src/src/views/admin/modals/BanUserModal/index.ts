import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface BanUserModalParams {
  uid: number
}

export default function (params: BanUserModalParams): Promise<string | null> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...params,
      onClose: (result: boolean, remark?: string) => {
        resolve(result ? (remark || null) : null)
        destroyModalApp(app, mountNode)
      }
    })
    app.mount(mountNode)
  })
}

