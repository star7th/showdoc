import myModal from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(myModal, {
      onClose: (res = false) => {
        resolve(res)
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}

