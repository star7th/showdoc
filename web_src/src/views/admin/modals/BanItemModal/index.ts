import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (params: { item_id: number; item_name?: string }): Promise<any> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...params,
      onClose: (result: boolean, data: any) => {
        resolve(result ? data : null)
        destroyModalApp(app, mountNode)
      }
    })
    app.mount(mountNode)
  })
}

