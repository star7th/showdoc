import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (options: {
  pageId: number
  onRestore?: (pageContent: string) => void
  onClose?: () => void
  // 是否允许恢复（编辑器页面可以恢复，展示页不能恢复）
  allowRecover?: boolean
  // 是否允许编辑备注
  allowEdit?: boolean
}): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      pageId: options.pageId,
      allowRecover: options.allowRecover ?? true,
      allowEdit: options.allowEdit ?? true,
      onRestore: (pageContent: string) => {
        options.onRestore?.(pageContent)
        destroyModalApp(app, mountNode)
        resolve(true)
      },
      onClose: () => {
        options.onClose?.()
        destroyModalApp(app, mountNode)
        resolve(false)
      }
    })
    app.mount(mountNode)
  })
}


