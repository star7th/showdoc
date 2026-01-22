import myModal from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface AlertOptions {
  callback?: () => void
  dangerouslyUseHTMLString?: boolean
}

// 全局锁，防止多个弹窗同时存在
let isShowing = false

export default function (msg: string = '', options?: AlertOptions): Promise<void> {
  return new Promise((resolve) => {
    // 如果已经有弹窗在显示，直接返回
    if (isShowing) {
      resolve()
      return
    }

    isShowing = true
    const { app, mountNode } = createModalApp(myModal, {
      msg,
      dangerouslyUseHTMLString: options?.dangerouslyUseHTMLString,
      onClose: () => {
        // 先执行 callback（如果存在）
        if (options?.callback) {
          options.callback()
        }
        isShowing = false
        resolve()
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}

