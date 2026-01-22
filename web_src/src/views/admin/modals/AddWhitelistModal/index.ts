import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (params: { item_id: number; item_name?: string }): Promise<{ remark?: string } | null> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...params,
      onClose: (result: boolean, data: any) => {
        if (result) {
          resolve(data)
        } else {
          resolve(null)
        }
        destroyModalApp(app, mountNode)
      }
    })
    app.mount(mountNode)
  })
}

