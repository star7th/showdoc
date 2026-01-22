import myModal from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

// 全局锁，防止多个弹窗同时存在
let isShowing = false

export default function () {
  return new Promise<boolean>((resolve) => {
    // 如果已经有弹窗在显示，直接返回 false
    if (isShowing) {
      console.warn('[PushUrlModal] 已经有弹窗在显示，忽略新的调用')
      resolve(false)
      return
    }

    isShowing = true
    const { app, mountNode } = createModalApp(myModal, {
      onClose: (result: boolean) => {
        isShowing = false
        resolve(result)
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}
