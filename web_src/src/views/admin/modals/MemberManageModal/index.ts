import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface MemberManageModalParams {
  item_id: number
}

export default function (params: MemberManageModalParams): Promise<void> {
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

