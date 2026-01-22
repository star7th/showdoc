import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (options: {
  itemId: number
  parentCatId?: number
  onClose?: () => void
  onSuccess?: (newCatId: number, catName: string) => void
}): Promise<{ catId: number; catName: string }> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      itemId: options.itemId,
      parentCatId: options.parentCatId || 0,
      onClose: () => {
        options.onClose?.()
        destroyModalApp(app, mountNode)
        resolve({ catId: 0, catName: '' })
      },
      onSuccess: (catId: number, catName: string) => {
        options.onSuccess?.(catId, catName)
        destroyModalApp(app, mountNode)
        resolve({ catId, catName })
      },
    })
    app.mount(mountNode)
  })
}

