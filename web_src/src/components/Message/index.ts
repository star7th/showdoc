import myModal from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface MessageOptions {
  message: string
  timer?: number
  position?: 'top' | 'bottom'
}

function showMessage(options: MessageOptions): Promise<void> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(myModal, {
      message: options.message,
      timer: options.timer ?? 3000,
      position: options.position ?? 'top',
      onCancel: () => {
        resolve()
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}

export default {
  success: (message: string, timer?: number, position?: 'top' | 'bottom') =>
    showMessage({ message, timer, position }),
  error: (message: string, timer?: number, position?: 'top' | 'bottom') =>
    showMessage({ message, timer, position }),
  info: (message: string, timer?: number, position?: 'top' | 'bottom') =>
    showMessage({ message, timer, position }),
  show: (options: MessageOptions) => showMessage(options),
}
