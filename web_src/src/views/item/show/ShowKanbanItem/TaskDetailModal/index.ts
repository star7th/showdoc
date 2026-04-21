import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface TaskDetailModalParams {
  taskData: any
  taskPageId: string
  itemInfo: any
  lists: any[]
  members: any[]
}

export default function (params: TaskDetailModalParams): Promise<{ action: 'save' | 'delete' | 'close', data?: any }> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...params,
      onClose: (result: any) => {
        resolve(result || { action: 'close' })
        destroyModalApp(app, mountNode)
      }
    })
    app.mount(mountNode)
  })
}
