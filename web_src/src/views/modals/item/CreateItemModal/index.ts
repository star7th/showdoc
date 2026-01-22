import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface CreateItemParams {
  item_id?: string | number
  item_type?: string
  item_group_id?: number
}

export default function (params?: CreateItemParams): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      item_id: params?.item_id,
      item_type: params?.item_type,
      item_group_id: params?.item_group_id,
      onClose: (result: boolean) => {
        resolve(result)
        destroyModalApp(app, mountNode)
      }
    })
    app.mount(mountNode)
  })
}
