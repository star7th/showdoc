/**
 * EditormdEditor 类型定义
 */

/**
 * 编辑器模式
 */
export type EditorMode = 'editor' | 'preview' | 'html'

/**
 * 编辑器主题
 */
export type EditorTheme = 'light' | 'dark'

/**
 * 主题配置
 */
export interface ThemeConfig {
  editor?: EditorTheme
  preview?: EditorTheme
}

/**
 * 工具栏配置项
 */
export interface ToolbarItem {
  name: string
  icon?: string
  title?: string
  action?: string
}

/**
 * 上传配置
 */
export interface UploadConfig {
  handler?: (files: File[]) => Promise<{ url: string }[]>
  maxFileSize?: number
  accept?: string
  multiple?: boolean
}

/**
 * 功能配置
 */
export interface FeatureConfig {
  // 同步滚动
  syncScroll?: boolean
  // 目录（TOC）
  toc?: boolean
  // 自动插入 TOC
  autoInsertToc?: boolean
  // 全屏
  fullscreen?: boolean
  // Mermaid 图表
  mermaid?: boolean
  // PlantUML
  plantuml?: boolean
  // 思维导图
  mindmap?: boolean
  // 流程图
  flowchart?: boolean
  // 序列图
  sequenceDiagram?: boolean
  // 数学公式
  tex?: boolean
  // 任务列表
  taskList?: boolean
  // 代码高亮
  codeHighlight?: boolean
  // 图片上传
  imageUpload?: boolean
  // 视频
  video?: boolean
}

/**
 * 编辑器暴露的方法
 */
export interface EditormdEditorExpose {
  // 获取 Markdown 内容
  getMarkdown: () => string
  // 获取内容（别名，兼容旧版本）
  getValue: () => string
  // 获取 HTML 内容
  getHTML: () => string
  // 插入内容到编辑器（插入到光标处）
  insertValue: (content: string) => void
  // 设置内容
  setValue: (markdown: string) => void
  // 获取选中文本
  getSelection: () => string
  // 清空编辑器
  clear: () => void
  // 设置光标位置
  setCursor: (position: { line: number; ch: number }) => void
  // 聚焦
  focus: () => void
  // 失焦
  blur: () => void
  // 关闭预览
  unwatch: () => void
  // 开启预览
  watch: () => void
  // 刷新预览
  preview: () => void
  // 获取编辑器实例
  getInstance: () => any
}

/**
 * 编辑器事件
 */
export interface EditorEvents {
  'update:modelValue'?: (value: string) => void
  'change'?: (value: string) => void
  'load'?: (editor: any) => void
  'upload'?: (files: File[]) => Promise<{ url: string }[]>
  'task-toggle'?: (payload: { index: number; checked: boolean }) => void
}
