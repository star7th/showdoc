import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface AttornItemModalParams {
  item_id: number
}

export default function (params: AttornItemModalParams): Promise<boolean> {
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

