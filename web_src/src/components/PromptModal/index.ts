import myModal from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

// 全局锁，防止多个弹窗同时存在
let isShowing = false

export default function (title: string = '', input: string = '', placeholder: string = '') {
  return new Promise<string>((resolve) => {
    // 如果已经有弹窗在显示，直接返回空字符串
    if (isShowing) {
      console.warn('[PromptModal] 已经有弹窗在显示，忽略新的调用')
      resolve('')
      return
    }

    isShowing = true
    const { app, mountNode } = createModalApp(myModal, {
      title,
      input,
      placeholder,
      onClose: (result: string) => {
        isShowing = false
        resolve(result || '')
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}

