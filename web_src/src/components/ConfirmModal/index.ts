import myModal from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface ConfirmOptions {
  msg: string
  title?: string
  confirmText?: string
  cancelText?: string
}

// 全局锁，防止多个弹窗同时存在
let isShowing = false

export default function (options: string | ConfirmOptions, confirmText?: string): Promise<boolean> {
  let msg = ''
  let title = undefined
  let confirmBtnText = undefined
  let cancelBtnText = undefined

  if (typeof options === 'string') {
    msg = options
    confirmBtnText = confirmText
  } else {
    msg = options.msg
    title = options.title
    confirmBtnText = options.confirmText
    cancelBtnText = options.cancelText
  }

  return new Promise((resolve) => {
    // 如果已经有弹窗在显示，直接返回 false
    if (isShowing) {
      console.warn('[ConfirmModal] 已经有弹窗在显示，忽略新的调用')
      resolve(false)
      return
    }

    isShowing = true
    const { app, mountNode } = createModalApp(myModal, {
      msg,
      title,
      OkText: confirmBtnText,
      cancelText: cancelBtnText,
      onClose: (res = false) => {
        isShowing = false
        resolve(res)
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}

