/**
 * EditormdEditor 常量定义
 */

import type { FeatureConfig, ToolbarItem } from './types'

/**
 * 编辑器默认配置
 * 注意：path 和 katexURL 需要在运行时根据 getStaticPath() 动态设置
 * 因为 DEFAULT_EDITOR_CONFIG 是静态常量，无法访问 window.DocConfig
 */
export const DEFAULT_EDITOR_CONFIG = {
  // path 将在 initEditor 中动态设置
  // path: '/static/editor.md/lib/',
  height: '70vh',
  syncScrolling: 'single',
  htmlDecode: 'style,script,iframe|filterXSS',
  previewCodeHighlight: false, // 使用 highlight.js 替代
  taskList: true,
  atLink: false,
  emailLink: false,
  tex: true,
  flowChart: true,
  sequenceDiagram: true,
  // katexURL 将在 initEditor 中动态设置
  // katexURL: { css: '', js: '' },
  imageUpload: true,
  imageFormats: [
    'jpg',
    'jpeg',
    'gif',
    'png',
    'bmp',
    'webp',
    'JPG',
    'JPEG',
    'GIF',
    'PNG',
    'BMP',
    'WEBP'
  ],
  imageUploadURL: '',
  toolbarIconsClass: {
    toc: 'fa-bars ',
    mindmap: 'fa-sitemap ',
    plantuml: 'fa-random ',
    mermaid: 'fa-pie-chart ',
    video: 'fa-file-video-o',
    center: 'fa-align-center',
    tasklist: 'fa-check-square-o'
  },
  toolbarIcons: function() {
    return [
      'undo',
      'redo',
      '|',
      'bold',
      'del',
      'italic',
      'quote',
      '|',
      'toc',
      'mindmap',
      'plantuml',
      'mermaid',
      'tasklist',
      'h1',
      'h2',
      'h3',
      'h4',
      'h5',
      'h6',
      '|',
      'list-ul',
      'list-ol',
      'hr',
      'center',
      '|',
      'link',
      'reference-link',
      'image',
      'video',
      'code',
      'code-block',
      'table',
      'datetime',
      'html-entities',
      'pagebreak',
      '|',
      'watch',
      'fullscreen',
      'clear',
      'search',
      '|',
      'help'
    ]
  },
}

/**
 * 默认功能配置
 */
export const DEFAULT_FEATURE_CONFIG: Required<FeatureConfig> = {
  syncScroll: true,
  toc: true,
  autoInsertToc: true,
  fullscreen: true,
  mermaid: true,
  plantuml: true,
  mindmap: true,
  flowchart: true,
  sequenceDiagram: true,
  tex: true,
  taskList: true,
  codeHighlight: true,
  imageUpload: true,
  video: true,
}

/**
 * 默认工具栏配置
 */
export const DEFAULT_TOOLBAR: ToolbarItem[] = [
  'undo',
  'redo',
  '|',
  'bold',
  'del',
  'italic',
  'quote',
  '|',
  'toc',
  'mindmap',
  'plantuml',
  'mermaid',
  'tasklist',
  'h1',
  'h2',
  'h3',
  'h4',
  'h5',
  'h6',
  '|',
  'list-ul',
  'list-ol',
  'hr',
  'center',
  '|',
  'link',
  'reference-link',
  'image',
  'video',
  'code',
  'code-block',
  'table',
  'datetime',
  'html-entities',
  'pagebreak',
  '|',
  'watch',
  'fullscreen',
  'clear',
  'search',
  '|',
  'help',
]

/**
 * 工具栏图标类名配置
 * 使用 FontAwesome 图标
 */
export const TOOLBAR_ICONS_CLASS = {
  undo: 'fa-undo',
  redo: 'fa-repeat',
  bold: 'fa-bold',
  del: 'fa-strikethrough',
  italic: 'fa-italic',
  quote: 'fa-quote-left',
  uppercase: 'fa-font',
  h1: 'fa-bold',
  h2: 'fa-bold',
  h3: 'fa-bold',
  h4: 'fa-bold',
  h5: 'fa-bold',
  h6: 'fa-bold',
  'list-ul': 'fa-list-ul',
  'list-ol': 'fa-list-ol',
  hr: 'fa-minus',
  link: 'fa-link',
  'reference-link': 'fa-external-link',
  image: 'fa-image-o',
  code: 'fa-code-o',
  'code-block': 'fa-file-code-o',
  table: 'fa-table',
  datetime: 'fa-clock-o',
  'html-entities': 'fa-crop',
  pagebreak: 'fa-newspaper-o',
  watch: 'fa-eye',
  unwatch: 'fa-eye-slash',
  preview: 'fa-desktop',
  fullscreen: 'fa-expand',
  exitfullscreen: 'fa-compress',
  clear: 'fa-eraser',
  search: 'fa-search',
  help: 'fa-question-circle',
  info: 'fa-info-circle',
  // 自定义图标
  toc: 'fa-bars',
  mindmap: 'fa-sitemap',
  plantuml: 'fa-random',
  mermaid: 'fa-pie-chart',
  video: 'fa-file-video-o',
  center: 'fa-align-center',
  tasklist: 'fa-check-square-o',
}

/**
 * 支持的图片格式
 */
export const DEFAULT_IMAGE_FORMATS = [
  'jpg',
  'jpeg',
  'gif',
  'png',
  'bmp',
  'webp',
  'JPG',
  'JPEG',
  'GIF',
  'PNG',
  'BMP',
  'WEBP',
]

/**
 * 默认最大文件大小（10MB）
 */
export const DEFAULT_MAX_FILE_SIZE = 10 * 1024 * 1024

/**
 * 代码高亮主题
 */
export const CODE_HIGHLIGHT_THEME = 'atom-one-dark'

/**
 * 上传状态文本
 */
export const UPLOAD_TEXT = {
  uploading: '上传中...',
  success: '上传成功',
  error: '上传失败',
}

/**
 * TOC 标记
 */
export const TOC_MARKER = '[TOC]'

/**
 * 自动插入 TOC 的模式
 */
export enum AutoInsertTocMode {
  NEVER = 'never', // 不自动插入
  ALWAYS = 'always', // 始终自动插入
  IF_MISSING = 'if-missing', // 仅当不存在 [TOC] 时插入
}

/**
 * 默认自动插入 TOC 模式
 */
export const DEFAULT_AUTO_INSERT_TOC_MODE = AutoInsertTocMode.IF_MISSING
