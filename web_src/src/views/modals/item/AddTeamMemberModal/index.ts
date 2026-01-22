import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (team_id: number): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      team_id,
      onClose: (result: boolean) => {
        resolve(result)
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}

