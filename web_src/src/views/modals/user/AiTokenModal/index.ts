import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export interface AiTokenModalResult {
  success: boolean
}

/**
 * 「AI 接入」弹窗（由原 AI 令牌管理弹窗升级）
 *
 * 顶部栏图标、助手窗引导项、空状态、推广条等入口统一调用本函数。
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

/** 打开「AI 接入」弹窗（统一入口别名，语义更直观） */
export const openAiAccessModal = AiTokenModal
