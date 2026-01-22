import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (options: {
  itemId: number
  catId: number
  onClose?: () => void
  onSelect?: (catId: number) => void
}): Promise<number> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      itemId: options.itemId,
      catId: options.catId,
      onClose: () => {
        options.onClose?.()
        destroyModalApp(app, mountNode)
        resolve(0)
      },
      onSelect: (catId: number) => {
        options.onSelect?.(catId)
        destroyModalApp(app, mountNode)
        resolve(catId)
      },
    })
    app.mount(mountNode)
  })
}

