import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export interface PaymentVerifyModalProps {
  isVerified: boolean
}

export default function (props: PaymentVerifyModalProps): Promise<void> {
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

