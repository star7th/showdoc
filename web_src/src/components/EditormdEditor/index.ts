/**
 * EditormdEditor 导出
 */

import EditormdEditor from './index.vue'
import ShowdocAdapter from './ShowdocAdapter.vue'

// 导出组件
export { EditormdEditor, ShowdocAdapter }

// 默认导出适配器（方便使用）
export default ShowdocAdapter

// 导出类型
export * from './types'

// 导出常量
export * from './constants'
