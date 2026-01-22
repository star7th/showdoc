import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface AddMemberToItemModalParams {
  item_id: number
}

export default function (params: AddMemberToItemModalParams): Promise<string | null> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...params,
      onClose: (result: boolean, username?: string) => {
        resolve(result ? (username || null) : null)
        destroyModalApp(app, mountNode)
      }
    })
    app.mount(mountNode)
  })
}


