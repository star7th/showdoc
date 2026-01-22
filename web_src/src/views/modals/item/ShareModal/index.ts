import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface ShareModalParams {
  item_domain: string
  item_id: number
  page_id?: number
  page_unique_key?: string
  page_title?: string
  item_info?: {
    item_edit?: boolean
    unique_key?: string
    page_title?: string
  }
}

export default function (params: ShareModalParams): Promise<boolean> {
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

