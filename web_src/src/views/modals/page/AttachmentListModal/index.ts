import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (props: {
  itemId: number
  pageId: number
  manage?: boolean
  onClose: () => void
  onInsert?: (markdown: string) => void
}): Promise<void> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...props,
      onClose: () => {
        props.onClose()
        destroyModalApp(app, mountNode)
        resolve()
      }
    })
    app.mount(mountNode)
  })
}

