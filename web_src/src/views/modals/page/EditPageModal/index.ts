import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

interface EditPageModalOptions {
  itemId: number
  editPageId?: number // 编辑模式: 传入pageId
  copyPageId?: number // 复制模式: 传入要复制的pageId
  catId?: number // 在指定目录下创建页面
  onClose?: (result: boolean) => void
}

export default function (options: EditPageModalOptions): Promise<boolean> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      itemId: options.itemId,
      editPageId: options.editPageId || 0,
      copyPageId: options.copyPageId || 0,
      catId: options.catId || 0,
      onClose: (result: boolean) => {
        resolve(result)
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}

