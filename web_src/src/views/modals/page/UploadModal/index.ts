import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function (props: {
  itemId: number
  pageId: number
  callback: () => void
}): Promise<void> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      ...props,
      callback: () => {
        props.callback()
        destroyModalApp(app, mountNode)
        resolve()
      }
    })
    app.mount(mountNode)
  })
}

