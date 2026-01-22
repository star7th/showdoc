import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (item_id: string | number, team_id: number): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      item_id,
      team_id,
      onClose: (result: boolean) => {
        resolve(result)
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}

