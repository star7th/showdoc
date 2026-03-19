import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export interface AiTokenModalResult {
  success: boolean
}

/**
 * AI Token 管理弹窗
 *
 * 用法：
 * const result = await AiTokenModal()
 */
export default function AiTokenModal(): Promise<AiTokenModalResult> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      onClose: (result: boolean) => {
        resolve({ success: result })
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}
