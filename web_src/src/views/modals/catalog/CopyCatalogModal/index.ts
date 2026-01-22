import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (props: { catId: number; itemId: number }): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...props,
      onClose: () => {
        resolve(true) // 弹窗关闭后刷新
        destroyModalApp(app, mountNode)
      }
    })
    app.mount(mountNode)
  })
}

