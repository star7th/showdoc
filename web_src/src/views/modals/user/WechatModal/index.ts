import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export interface WechatModalProps {
  isBound: boolean
}

export default function (props: WechatModalProps): Promise<void> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...props,
      onClose: () => {
        resolve()
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}

