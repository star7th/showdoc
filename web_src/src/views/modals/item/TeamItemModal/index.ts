import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (params: { team_id: number; team_manage: number }): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...params,
      onClose: (result: boolean) => {
        resolve(result)
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}

