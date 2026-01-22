<template>
  <div :id="editorId" class="main-editor"></div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { getUserInfo } from '@/models/user'
import { getServerHost, getStaticPath } from '@/utils/system'
import Viewer from 'viewerjs'
import 'viewerjs/dist/viewer.css'

// ============================================
// Props 定义
// ============================================
interface EditormdEditorProps {
  // v-model 绑定
  modelValue: string
  // 编辑器 ID
  id?: string
  // 模式：editor(编辑) | preview(预览) | html(纯 HTML 渲染)
  mode?: 'editor' | 'preview' | 'html'
  // 高度
  height?: string
  // 允许在阅读模式下切换任务列表复选框
  taskToggle?: boolean
  // 关键字高亮
  keyword?: string
  // 编辑器路径
  editorPath?: string
  // 工具栏配置
  toolbar?: any[]
  // 功能配置
  features?: any
  // 主题配置
  theme?: any
  // 上传配置
  upload?: any
}

const props = withDefaults(defineProps<EditormdEditorProps>(), {
  modelValue: '',
  id: () => `editor-md-${Math.random().toString(36).substr(2, 9)}`,
  mode: 'editor',
  height: '70vh',
  taskToggle: true,
  keyword: '',
  editorPath: '',
  toolbar: () => [
    'undo', 'redo', '|',
    'bold', 'del', 'italic', 'quote', '|',
    'mindmap', 'plantuml', 'mermaid', 'tasklist',
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6', '|',
    'list-ul', 'list-ol', 'hr', 'center', '|',
    'link', 'reference-link', 'image', 'video', 'code', 'code-block', 'table',
    'datetime', 'html-entities', 'pagebreak', '|',
    'watch', 'fullscreen', 'clear', 'search', '|',
    'help'
  ],
  features: () => ({}),
  theme: () => ({}),
  upload: () => ({})
})

// ============================================
// Emits 定义
// ============================================
interface EditorEmits {
  (e: 'update:modelValue', value: string): void
  (e: 'change', value: string): void
  (e: 'load', editor: any): void
  (e: 'task-toggle', payload: { index: number; checked: boolean }): void
}

const emit = defineEmits<EditorEmits>()

// ============================================
// 内部状态
// ============================================
// 🔴 关键：instance 使用普通变量而不是 ref（避免 Vue 响应式包装）
//
// 【重要】为什么不能用 ref 存储 Editor.md 实例？
//
// 问题表现：
// - 编辑器可以正常显示
// - 但是插入文本后，无法继续编辑
// - 点击插入的内容，焦点无法回到编辑区域
// - 就像有一层隐藏的 DOM 阻挡了交互
//
// 根本原因：
// Vue 的响应式系统会通过 Proxy 包装对象，拦截对象的属性访问和修改。
// Editor.md 内部依赖于对象的原型链和属性的直接访问，当被 Vue 包装后，
// 内部的代码逻辑会被破坏，导致编辑器交互功能失效。
//
// 错误写法 ❌：
//   const instance = ref<any>(null)
//   instance.value = editormd(...)
//
// 正确写法 ✅：
//   let instance: any = null
//   instance = editormd(...)
//
// 参考：
// 这个问题在迁移过程中花了大量时间排查，最终发现是 Vue 响应式包装导致。
// 切换为普通变量后问题立即解决。
//
let instance: any = null
const editorId = ref(props.id)
const editorPathRef = ref('')
const userToken = ref('')
const mermaidLoaded = ref(false)

// ============================================
// 加载脚本和样式
// ============================================
const loadedScripts = new Set<string>()
const loadedStyles = new Set<string>()

const loadScript = (url: string): Promise<void> => {
  return new Promise((resolve, reject) => {
    // 检查是否已加载（使用完整 URL 作为 key）
    if (loadedScripts.has(url)) {
      resolve()
      return
    }

    const script = document.createElement('script')
    script.src = url
    script.onload = () => {
      loadedScripts.add(url)
      resolve()
    }
    script.onerror = () => {
      reject(new Error(`Failed to load ${url}`))
    }
    document.head.appendChild(script)
  })
}

const loadCSS = (href: string): Promise<void> => {
  return new Promise((resolve, reject) => {
    // 检查是否已加载（使用完整 URL 作为 key）
    if (loadedStyles.has(href)) {
      resolve()
      return
    }

    const link = document.createElement('link')
    link.rel = 'stylesheet'
    link.href = href
    link.onload = () => {
      loadedStyles.add(href)
      resolve()
    }
    link.onerror = () => {
      reject(new Error(`Failed to load CSS: ${href}`))
    }
    document.head.appendChild(link)
  })
}

// ============================================
// 工具函数
// ============================================
const htmlDecode = (str: string): string => {
  if (!str || str.length === 0) return ''
  let s = str
  s = s.replace(/&amp;/g, '&')
  s = s.replace(/&lt;/g, '<')
  s = s.replace(/&gt;/g, '>')
  s = s.replace(/&nbsp;/g, ' ')
  s = s.replace(/&#39;/g, "'")
  s = s.replace(/&quot;/g, '"')
  return s
}

const parseURL = (url: string) => {
  const a = document.createElement('a')
  a.href = url
  return {
    source: url,
    protocol: a.protocol.replace(':', ''),
    host: a.hostname,
    hostname: a.hostname,
    port: a.port,
    query: a.search,
    params: (() => {
      const params: Record<string, string> = {}
      const seg = a.search.replace(/^\?/, '').split('&')
      for (let i = 0; i < seg.length; i++) {
        if (seg[i]) {
          const p = seg[i].split('=')
          params[p[0]] = p[1]
        }
      }
      return params
    })(),
    hash: a.hash.replace('#', ''),
    path: a.pathname.replace(/^([^/])/, '/$1'),
    pathname: a.pathname.replace(/^([^/])/, '/$1')
  }
}

// ============================================
// 处理内容
// ============================================
const dealWithContent = async () => {
  const $: any = (window as any).$

  // 加载 highlight.js 并执行代码高亮
  const highlightJsUrl = `${editorPathRef.value}highlight/highlight.min.js?rand=2`
  try {
    await loadScript(highlightJsUrl)
    const hljs = (window as any).hljs
    if (hljs && hljs.highlightAll) {
      try {
        hljs.highlightAll()
        // 等待 DOM 更新
        await new Promise(resolve => setTimeout(resolve, 100))
      } catch (e) {
        // highlightAll error ignored
      }
    }
  } catch (e) {
    // highlight.js load error ignored
  }

  // 表格滚动条处理
  if ($ && $.each) {
    try {
      $(`#${editorId.value} table`).each(function() {
        const $table = $(this)
        const parent = $table.parent()
        if (!parent.hasClass('table-wrapper')) {
          $table.wrap('<div style="width: 100%; overflow-x: auto;"></div>')
        }
      })
    } catch (e) {
      // Table wrapper error ignored
    }
  }

  // 超链接处理
  if ($) {
    $(`#${editorId.value} a[href^="http"]`).click(function(this: HTMLElement) {
      const $this = $(this)
      const url = $this.attr('href')
      const obj = parseURL(url)
      const windowLocation = window.location
      if (
        (windowLocation.hostname === obj.hostname || obj.hostname === 'www.showdoc.cc') &&
        windowLocation.pathname === obj.pathname
      ) {
        window.location.href = url
        if (obj.hash) {
          window.location.reload()
        }
      } else {
        window.open(url, '_blank')
      }
      return false
    })
  }

  // 表格行背景色处理
  if ($ && $.each) {
    try {
      $(`#${editorId.value} table tbody tr`).each(function(this: HTMLElement) {
        const $tr = $(this)
        const $tds = $tr.find('td')
        const td2 = $tds.eq(1).html() || ''
        const td3 = $tds.eq(2).html() || ''
        const isObjectOrArray =
          td2 === 'object' ||
          td2 === 'array[object]' ||
          td3 === 'object' ||
          td3 === 'array[object]'

        // 使用 CSS 变量替代硬编码颜色，支持主题切换
        if (isObjectOrArray) {
          $tr.css({ 'background-color': 'var(--editormd-table-alt-bg)' })
        } else {
          $tr.css('background-color', 'var(--editormd-table-row-bg)')
        }

        $tr.off('mouseenter mouseleave').hover(
          function() {
            $tr.css('background-color', 'var(--editormd-table-alt-bg)')
          },
          function() {
            if (isObjectOrArray) {
              $tr.css({ 'background-color': 'var(--editormd-table-alt-bg)' })
            } else {
              $tr.css('background-color', 'var(--editormd-table-row-bg)')
            }
          }
        )
      })
    } catch (e) {
      // Table row color error ignored
    }
  }

  // 表格宽度均分
  if ($) {
    try {
      const contentWidth = $(`#${editorId.value}`).width() || 722
      $(`#${editorId.value} table`).each(function(this: HTMLElement) {
        const $table = $(this)
        const $v = $table.get(0) as HTMLTableElement
        if ($v && $v.rows && $v.rows.length > 0) {
          const firstRow = $v.rows.item(0)
          if (firstRow) {
            const num = firstRow.cells.length
            const colWidth = Math.floor(contentWidth / num) - 2
            if (num <= 5) {
              $table.find('th').css('width', colWidth.toString() + 'px')
            }
          }
        }
      })
    } catch (e) {
      // Table width error ignored
    }
  }

  // 图片点击放大
  if ($) {
    const $container = $(`#${editorId.value}`)
    // 移除之前绑定的 Viewer 实例
    if (($container.data('viewer') as any)) {
      ($container.data('viewer') as any).destroy()
      $container.removeData('viewer')
    }

    // 创建新的 Viewer 实例
    const viewerInstance = new Viewer($container.get(0), {
      url: 'src',
      title: false,
      toolbar: {
        zoomIn: 1,
        zoomOut: 1,
        oneToOne: 1,
        reset: 1,
        prev: 1,
        play: false,
        next: 1,
        rotateLeft: 1,
        rotateRight: 1,
        flipHorizontal: 1,
        flipVertical: 1
      }
    })
    // 保存 Viewer 实例以便销毁
    $container.data('viewer', viewerInstance)
  }

  // 关键字高亮
  if (props.keyword && $ && $.fn && $.fn.mark) {
    try {
      const $container = $(`#${editorId.value}`)
      $container.unmark()
      $container.mark(props.keyword, {
        separateWordSearch: true,
        caseSensitive: false,
        accuracy: 'partially',
        ignoreJoiners: true
      })
    } catch (e) {
      // Mark keyword error ignored
    }
  }

  // 代码块复制按钮（只对包含 code 标签的 pre 添加复制按钮）
  if ($ && $.fn) {
    try {
      const codeBlocks = $(`#${editorId.value} pre:has(> code)`)
      codeBlocks.each(function(this: HTMLElement) {
        const $pre = $(this)
        if ($pre.find('.code-copy-btn').length === 0) {
          const $btn = $('<span class="code-copy-btn">复制</span>')
          $btn.prependTo($pre)
        }
      })

      $(`#${editorId.value}`).off('click', '.code-copy-btn').on('click', '.code-copy-btn', function(this: HTMLElement) {
        const $btn = $(this)
        const $pre = $btn.parent()
        const codeText = $pre.text().trim()

        navigator.clipboard.writeText(codeText).then(() => {
          $btn.text('复制成功')
          setTimeout(() => {
            $btn.text('复制')
          }, 1500)
        }).catch(() => {
          // Copy failed ignored
        })
      })
    } catch (e) {
      // Copy button error ignored
    }
  }

  // 任务列表交互（在预览和纯 HTML 模式下启用）
  if ((props.mode === 'html' || props.mode === 'preview') && $ && $.each) {
    const $container = $(`#${editorId.value}`)
    const $checkboxes = $container
      .find('input[type="checkbox"]')
      .filter(function(this: HTMLElement) {
        return $(this).closest('pre, code, .editormd-code-block, .hljs').length === 0
      })

    $container.off('change.tasklist')

    if (!props.taskToggle) {
      $checkboxes.prop('disabled', true)
    } else {
      $checkboxes.each(function(this: HTMLElement, index: number) {
        const $checkbox = $(this)
        $checkbox.prop('disabled', false)
        $checkbox.attr('data-task-index', index.toString())
      })

      $container.on('change.tasklist', 'input[type="checkbox"]', function(this: HTMLElement) {
        const $target = $(this)
        if ($target.closest('pre, code, .editormd-code-block, .hljs').length > 0) {
          return
        }
        const index = parseInt($target.attr('data-task-index') || '-1')
        if (isNaN(index)) return
        const checked = $target.is(':checked')
        emit('task-toggle', { index, checked })
      })
    }
  }
}

// ============================================
// 加载依赖
// ============================================
const loadEditormdDeps = async (editorPath: string): Promise<void> => {
  try {
    // 第一批：jquery 和 raphael
    await Promise.all([
      loadScript(`${editorPath}jquery.min.js`),
      loadScript(`${editorPath}lib/raphael.min.js`)
    ])

    // 第二批：d3 和 flowchart
    await Promise.all([
      loadScript(`${editorPath}lib/d3@5.min.js`),
      loadScript(`${editorPath}lib/flowchart.min.js?v=2`)
    ])

    // 第三批：其他依赖
    await Promise.all([
      loadScript(`${editorPath}xss.min.js`),
      loadScript(`${editorPath}lib/marked.min.js`),
      loadScript(`${editorPath}lib/underscore.min.js`),
      loadScript(`${editorPath}lib/sequence-diagram.min.js`),
      loadScript(`${editorPath}lib/jquery.flowchart.min.js`),
      loadScript(`${editorPath}lib/jquery.mark.min.js`),
      loadScript(`${editorPath}lib/plantuml.js?v=2`),
      loadScript(`${editorPath}lib/view.min.js`),
      loadScript(`${editorPath}lib/transform.min.js`)
    ])

    // 加载 editormd.js
    const editormdJsUrl = `${editorPath}editormd.js?v=46`
    await loadScript(editormdJsUrl)

    // 验证 editormd 对象是否已注册到全局
    if (!((window as any).editormd)) {
      throw new Error('editormd object not found after loading script')
    }
  } catch (error) {
    throw error
  }
}

// ============================================
// 初始化编辑器
// ============================================
const initEditor = async () => {
  try {
    // 获取用户 Token
    const userInfo = await getUserInfo()
    if (userInfo) {
      userToken.value = userInfo.user_token || ''
    }

    // 获取编辑器路径
    const staticPath = getStaticPath()
    editorPathRef.value = props.editorPath || `${staticPath}editor.md/`

    // CSS 已通过 import 方式在 style 标签中加载（优先级更高）
    // await loadCSS(`${editorPathRef.value}css/editormd.css`)
    // await loadCSS(`${editorPathRef.value}css/editormd.preview.css`)

    // 加载 Editormd 依赖
    await loadEditormdDeps(editorPathRef.value)

    const editormd = (window as any).editormd
    if (!editormd) {
      return
    }

    // 按模式处理 Markdown，预览/HTML 模式下去掉 [TOC] 标记，交给新版 TOC 组件处理
    const rawMarkdown = props.modelValue || ''
    const markdown =
      props.mode === 'editor'
        ? rawMarkdown
        : rawMarkdown.replace(/\[TOC\]\s*/gi, '')

    // 构建编辑器配置
    const editorConfig: any = {
      path: `${editorPathRef.value}lib/`,
      height: props.height,
      markdown,
      taskList: true,
      atLink: false,
      emailLink: false,
      tex: true,
      flowChart: true,
      sequenceDiagram: true,
      // 关闭编辑器自身的 TOC 渲染，避免与新版 TOC 组件重复
      toc: false,
      tocm: false,
      tocDropdown: false,
      syncScrolling: 'single',
      htmlDecode: 'style,script,iframe|filterXSS',
      imageUpload: true,
      imageFormats: [
        'jpg', 'jpeg', 'gif', 'png', 'bmp', 'webp',
        'JPG', 'JPEG', 'GIF', 'PNG', 'BMP', 'WEBP'
      ],
      toolbar: props.toolbar,
      toolbarIcons: () => props.toolbar,
      toolbarIconsClass: {
        toc: 'fa-bars ',
        mindmap: 'fa-sitemap ',
        plantuml: 'fa-random ',
        mermaid: 'fa-pie-chart ',
        video: 'fa-file-video-o',
        center: 'fa-align-center',
        tasklist: 'fa-check-square-o'
      },
      toolbarHandlers: {
        video: function(cm: any, icon: any, cursor: any, selection: string) {
          cm.replaceSelection('\r\n<video src="http://your-site.com/your.mp4" style="width: 100%; height: 100%;" controls="controls"></video>\r\n')
          if (selection === '') {
            cm.setCursor(cursor.line, cursor.ch + 1)
          }
        },
        tasklist: function(cm: any, icon: any, cursor: any, selection: string) {
          const text = `## 任务清单
### 今日
- [ ] 整理站内消息与通知
- [ ] 跟进两条用户反馈
- [x] 合并并审核一个 PR

### 本周
- [ ] 完成文档目录整理与迁移
- [ ] 优化图片加载与懒加载策略

### 待办
- [ ] 编写部署与备份说明
- [ ] 补充接口错误码表
`
          cm.replaceSelection(text)
          if (selection === '') {
            cm.setCursor(cursor.line, cursor.ch + 1)
          }
        },
        mindmap: function(cm: any, icon: any, cursor: any, selection: string) {
          const text = `\`\`\`mindmap
# 一级
## 二级分支1
### 三级分支1
### 三级分支2
## 二级分支2
### 三级分支3
### 三级分支4
\`\`\``
          cm.replaceSelection(text)
          if (selection === '') {
            cm.setCursor(cursor.line, cursor.ch + 1)
          }
        },
        plantuml: function(cm: any, icon: any, cursor: any, selection: string) {
          const text = `\`\`\`plantuml
@startuml
actor 用户
用户 -> 系统: 请求
系统 --> 用户: 响应
@enduml
\`\`\``
          cm.replaceSelection(text)
          if (selection === '') {
            cm.setCursor(cursor.line, cursor.ch + 1)
          }
        },
        mermaid: function(cm: any, icon: any, cursor: any, selection: string) {
          const text = `\`\`\`mermaid
graph LR
    A[开始] --> B{判断}
    B -->|是| C[执行]
    B -->|否| D[结束]
    C --> D
\`\`\``
          cm.replaceSelection(text)
          if (selection === '') {
            cm.setCursor(cursor.line, cursor.ch + 1)
          }
        },
        center: function(cm: any, icon: any, cursor: any, selection: string) {
          cm.replaceSelection('<center>' + selection + '</center>')
          if (selection === '') {
            cm.setCursor(cursor.line, cursor.ch + 1)
          }
        }
      },
      lang: {
        toolbar: {
          mindmap: '插入思维导图',
          plantuml: '插入UML图',
          mermaid: '插入Mermaid图表',
          video: '插入视频',
          center: '居中',
          tasklist: '插入任务列表'
        }
      },
      onchange: () => {
        if (instance && instance.getMarkdown) {
          const markdown = instance.getMarkdown()
          emit('update:modelValue', markdown)
          emit('change', markdown)
        }
        dealWithContent()
      },
      previewCodeHighlight: false,
      katexURL: {
        css: '',
        js: ''
      },
      // 在编辑器完全初始化后执行 dealWithContent
      onload: () => {
        // 使用多次 nextTick 确保 DOM 完全准备好
        nextTick(() => {
          nextTick(() => {
            dealWithContent()
          })
        })
      }
    }

    // 设置图片上传 URL
    const serverHost = getServerHost()
    if (serverHost.indexOf('?') > -1) {
      editorConfig.imageUploadURL =
        `${serverHost}/api/page/uploadImg&user_token=${userToken.value}`
    } else {
      editorConfig.imageUploadURL =
        `${serverHost}/api/page/uploadImg?user_token=${userToken.value}`
    }

    // 设置 katex 路径
    editorConfig.katexURL = {
      css: `${editorPathRef.value}katex/katex.min`,
      js: `${editorPathRef.value}katex/katex.min`
    }

    // 等待 DOM 准备好
    await nextTick()

    // 检查容器元素是否存在
    const editorElement = document.getElementById(editorId.value)
    if (!editorElement) {
      console.error(`[EditormdEditor] Editor element #${editorId.value} not found`)
      return
    }

    if (props.mode === 'editor') {
      // 编辑模式：默认加载 mermaid
      if (!mermaidLoaded.value) {
        await loadScript(`${editorPathRef.value}lib/mermaid.min.js`)
        mermaidLoaded.value = true
      }

      instance = editormd(editorId.value, editorConfig)
      // dealWithContent 将在 onload 回调中执行，确保编辑器完全初始化
    } else {
      // 预览模式：按需加载 mermaid
      const needMermaid = (props.modelValue || '').toLowerCase().indexOf('mermaid') > -1
      if (needMermaid && !mermaidLoaded.value) {
        await loadScript(`${editorPathRef.value}lib/mermaid.min.js`)
        mermaidLoaded.value = true
      }
      instance = editormd.markdownToHTML(editorId.value, editorConfig)
      // 预览模式：等待一小段时间确保 DOM 完全准备好
      setTimeout(() => {
        dealWithContent()
      }, 50)
    }

    emit('load', instance)
  } catch (error) {
    // Initialization error
  }
}

// ============================================
// 挂载时初始化
// ============================================
onMounted(async () => {
  try {
    await initEditor()
  } catch (error) {
    // init error
  }
})

// ============================================
// 卸载时清理
// ============================================
onBeforeUnmount(() => {
  // 销毁 Viewer 实例
  const $ = (window as any).$
  if ($ && editorId.value) {
    const $container = $(`#${editorId.value}`)
    if (($container.data('viewer') as any)) {
      ($container.data('viewer') as any).destroy()
      $container.removeData('viewer')
    }
  }

  if (instance) {
    try {
      const element = document.getElementById(editorId.value)
      if (element) {
        element.innerHTML = ''
      }
    } catch (error) {
      // destroy error
    }
    instance = null
  }
})

// ============================================
// 暴露的方法
// ============================================
defineExpose({
  getMarkdown: () => instance?.getMarkdown() || '',
  insertValue: (value: string) => {
    if (instance) {
      instance.insertValue(htmlDecode(value))
    }
  },
  editorUnwatch: () => instance?.unwatch?.(),
  editorWatch: () => instance?.watch?.(),
  setCursorToTop: () => instance?.setCursor?.({ line: 0, ch: 0 }),
  clear: () => instance?.clear?.(),
  getSelection: () => instance?.getSelection?.() || '',
  setCursor: (position: { line: number; ch: number }) => instance?.setCursor?.(position),
  focus: () => {
    if (instance?.focus) {
      instance.focus()
    } else if (instance?.cm) {
      instance.cm.focus()
    }
  },
  blur: () => instance?.blur?.(),
  preview: () => instance?.previewing?.(),
  fullscreen: () => instance?.fullscreen?.(),
  fullscreenExit: () => instance?.fullscreenExit?.(),
  getInstance: () => instance
})
</script>

<style scoped>
.main-editor {
  width: 100%;
}
</style>

<style>
/* 先引入 Editormd 基础样式（必须最先加载） */
@import '@/components/EditormdEditor/assets/css/editormd.css';
@import '@/components/EditormdEditor/assets/css/editormd.preview.css';

/* 引入字体修复 */
@import '@/components/EditormdEditor/themes/font-path-fix.css';

/* 引入亮色主题变量（默认） */
@import '@/components/EditormdEditor/themes/light-vars.css';

/* 引入暗色主题变量 */
@import '@/components/EditormdEditor/themes/dark-vars.css';

/* 最后引入基础样式（会使用上面的变量，并覆盖 editormd 默认样式） */
@import '@/components/EditormdEditor/themes/base.css';

/* 引入代码高亮主题 */
@import '@/components/EditormdEditor/assets/highlight/atom-one-dark.min.css';
</style>
