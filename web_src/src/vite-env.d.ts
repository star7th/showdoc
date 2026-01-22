/// <reference types="vite/client" />

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<{}, {}, any>
  export default component
}

declare module '@kangc/v-md-editor' {
  import { Plugin } from 'vue'
  const VueMarkdownEditor: Plugin
  export default VueMarkdownEditor
}

declare module '@kangc/v-md-editor/lib/theme/github.js' {
  const githubTheme: any
  export default githubTheme
}

// 全局 DocConfig 类型定义
interface Window {
  DocConfig?: {
    server?: string
    originalServer?: string
    lang?: string
    staticPath?: string
  }
  SHOWDOC_SERVER_CDN_STASTUS?: boolean
  SHOWDOC_CDN_STASTUS?: boolean
}

