import Component from './index.vue'
import { createModalApp, destroyModalApp } from '@/utils/modalAppFactory'

export interface CreateTokenModalParams {
  /** 编辑模式时传入的 Token ID */
  editTokenId?: string
  /** 项目列表 */
  projects: { item_id: string; item_name: string }[]
}

export interface CreateTokenModalResult {
  success: boolean
}

/**
 * 创建/编辑 AI Token 弹窗
 *
 * 用法：
 * // 创建模式
 * const result = await CreateTokenModal({ projects })
 *
 * // 编辑模式
 * const result = await CreateTokenModal({ editTokenId: '123', projects })
 */
export default function CreateTokenModal(
  params: CreateTokenModalParams,
): Promise<CreateTokenModalResult> {
  return new Promise((resolve) => {
    const { app, mountNode } = createModalApp(Component, {
      editTokenId: params.editTokenId || null,
      projects: params.projects,
      onClose: (result: boolean) => {
        resolve({ success: result })
        destroyModalApp(app, mountNode)
      },
    })
    app.mount(mountNode)
  })
}
