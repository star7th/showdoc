import ContextmenuModal from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export default function ({
  x = 0,
  y = 0,
  list = [],
}: ContextmenuModalInterface) {
  const { app, mountNode } = createModalApp(ContextmenuModal, {
    x,
    y,
    list,
    onCancel: () => {
      destroyModalApp(app, mountNode)
    }
  })
  app.mount(mountNode)
}

export interface ContextmenuModalInterface {
  x: number
  y: number
  list: ContextmenuModalItemInterface[]
}

export interface ContextmenuModalItemInterface {
  icon?: string[]
  img?: string
  text: string
  value?: any
  pro?: boolean
  iconStyle?: string
  hidden?: boolean
  checked?: boolean
  children?: ContextmenuModalItemInterface[]
  onclick?: Function
  shortcut?: string // 快捷键，例如 "Ctrl+C", "Delete", "Ctrl+D" 等
}

