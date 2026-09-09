<template>
  <div :class="['ai-message-bubble', message.role]">
    <div class="bubble-avatar">
      <i :class="message.role === 'user' ? 'fas fa-user' : 'fas fa-robot'"></i>
    </div>
    <div class="bubble-body">
      <!-- 状态提示（工具调用中） -->
      <div v-if="statusText" class="status-indicator">
        <a-spin size="small" />
        <span class="status-text">{{ statusText }}</span>
      </div>

      <!-- 消息内容 -->
      <div
        v-if="message.content"
        ref="bubbleContentRef"
        class="bubble-content"
        :class="{ 'is-streaming': isStreaming }"
      ></div>

      <!-- AI 思考中动画（无内容且正在加载） -->
      <div v-if="isLoading && !message.content && !statusText" class="typing-indicator">
        <span></span>
        <span></span>
        <span></span>
      </div>

      <!-- 引用链接 -->
      <div v-if="refs.length > 0" class="bubble-refs">
        <div class="refs-label">
          <i class="fas fa-link"></i> 引用来源
        </div>
        <div
          v-for="(ref, idx) in refsWithUrl"
          :key="idx"
          :class="['ref-link', { 'ref-link-static': !ref.clickable }]"
          @click="ref.clickable && openRef(ref)"
        >
          <i class="fas fa-file-alt"></i>
          {{ ref.title || ref.displayUrl }}
        </div>
      </div>

      <!-- 操作按钮（仅 assistant 消息） -->
      <div v-if="message.role === 'assistant' && message.content && message.id" class="bubble-actions">
        <a-tooltip title="重新生成">
          <i class="fas fa-redo-alt action-btn" :class="{ disabled: !message.id }" @click="message.id && $emit('regenerate', message)"></i>
        </a-tooltip>
        <a-tooltip title="有帮助">
          <i
            class="fas fa-thumbs-up action-btn"
            :class="{ active: message.feedback === 1, disabled: !message.id }"
            @click="message.id && $emit('feedback', message.id, message.feedback === 1 ? 0 : 1)"
          ></i>
        </a-tooltip>
        <a-tooltip title="没帮助">
          <i
            class="fas fa-thumbs-down action-btn"
            :class="{ active: message.feedback === 2, disabled: !message.id }"
            @click="message.id && $emit('feedback', message.id, message.feedback === 2 ? 0 : 2)"
          ></i>
        </a-tooltip>
        <a-tooltip :title="copySuccess ? $t('ai.ai_copied') : $t('ai.ai_copy')">
          <i :class="[copySuccess ? 'fas fa-check' : 'fas fa-copy', 'action-btn', { active: copySuccess }]" @click="copyContent"></i>
        </a-tooltip>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import type { AiChatMessage } from '@/api/aiAgent'
import { sanitizeHtml } from '@/utils/sanitize'

const router = useRouter()

/**
 * 将引用标记解析为 hash 路由安全的相对 URL（如 `#/page/123`）。
 * 开源版使用 createWebHashHistory，必须用 router.resolve 生成链接，
 * 避免硬编码 `/page/123` 导致新标签页丢失 hash 路由。
 */
const resolveAiHref = (kind: string, ref: { page_id?: number; item_id?: number; [k: string]: any }): string => {
  try {
    if (kind === 'page' && ref.page_id) {
      return ref.item_id
        ? router.resolve({ name: 'ItemShowPage', params: { item_id: String(ref.item_id), page_id: String(ref.page_id) } }).href
        : router.resolve({ name: 'PageShow', params: { page_id: String(ref.page_id) } }).href
    }
    if (kind === 'item' && ref.item_id) {
      return router.resolve({ name: 'ItemShow', params: { item_id: String(ref.item_id) } }).href
    }
    if (kind === 'item_list') return router.resolve({ name: 'ItemIndex' }).href
    if (kind === 'login') return router.resolve({ name: 'Login' }).href
    if (kind === 'register') return router.resolve({ name: 'Register' }).href
    if (kind === 'user_center') return router.resolve({ name: 'UserSetting' }).href
    if (kind === 'messages') return router.resolve({ name: 'MessageIndex' }).href
    if (kind === 'home') return router.resolve({ name: 'Index' }).href
  } catch {
    // ignore resolve errors
  }
  return ''
}

interface RefItem {
  kind: string
  page_id?: number
  title?: string
  url?: string
  [key: string]: any
}

const props = defineProps<{
  message: AiChatMessage & { feedback?: number }
  isLoading?: boolean
  statusText?: string
  refs?: RefItem[]
  itemId?: number
  /** [Fix 流式渲染] done 事件驱动的最终渲染回执序号，变化时触发完整重渲染（含半截标记闭合） */
  finalizeTick?: number
}>()

defineEmits<{
  regenerate: [message: any]
  feedback: [messageId: number, value: number]
}>()

const copySuccess = ref(false)

// ─── 增量 Markdown 渲染（打字机效果） ───────────────────
const isStreaming = ref(false)

/** 为每个 ref 生成可点击 URL（hash 路由安全） */
const refsWithUrl = computed(() => {
  return props.refs.map(ref => {
    let url = resolveAiHref(ref.kind, ref) || ref.url || ''
    let title = ref.page_title || ref.item_name || ref.title || ''
    let clickable = true

    if (ref.kind === 'page' && ref.page_id) {
      if (!title) title = `页面 #${ref.page_id}`
    } else if (ref.kind === 'item' && ref.item_id) {
      if (!title) title = `项目 #${ref.item_id}`
    } else if (ref.kind === 'item_list') {
      if (!title) title = '项目列表'
    } else if (ref.kind === 'login') {
      if (!title) title = '登录'
    } else if (ref.kind === 'register') {
      if (!title) title = '注册'
    } else if (ref.kind === 'user_center') {
      if (!title) title = '个人中心'
    } else if (ref.kind === 'messages') {
      if (!title) title = '消息中心'
    } else if (ref.kind === 'home') {
      if (!title) title = '首页'
    } else {
      // 未知 kind：显示为纯文本不可点击
      clickable = false
      if (!title) title = ref.kind
    }

    return { ...ref, url, title, displayUrl: url, clickable }
  })
})

/** 打开引用链接（新标签页） */
const openRef = (ref: RefItem & { url?: string }) => {
  if (ref.url) {
    window.open(ref.url, '_blank', 'noopener,noreferrer')
  }
}

/**
 * Markdown → HTML 渲染（支持代码块、列表、链接、图片、表格、粗体、斜体、标题）
 * @param text 要渲染的 Markdown 文本
 * @returns 渲染后的 HTML 字符串
 */
function renderMarkdown(text: string): string {
  if (!text) return ''
  let html = text

  // 1. 保护代码块（防止内部被转换）
  const codeBlocks: string[] = []
  html = html.replace(/```(\w*)\n([\s\S]*?)```/g, (_, lang, code) => {
    const idx = codeBlocks.length
    codeBlocks.push(`<pre><code class="language-${lang || 'text'}">${escapeHtml(code.trimEnd())}</code></pre>`)
    return `\x00CODEBLOCK${idx}\x00`
  })

  // 2. 行内代码（内容需 escape，防止 XSS）
  html = html.replace(/`([^`]+)`/g, (_, code) => `<code>${escapeHtml(code)}</code>`)

  // 3. 表格（GFM table）
  html = html.replace(/(^|\n)(\|.+\|)(\n)(\|[-:| ]+\|)((?:\n\|.+\|)*)/g, (_match, pre, header, _sep1, _sepRow, body) => {
    const headerCells = header.split('|').filter((c: string) => c.trim())
    const headerHtml = '<thead><tr>' + headerCells.map((c: string) => `<th>${c.trim()}</th>`).join('') + '</tr></thead>'
    const rows = body.split('\n').filter((r: string) => r.trim())
    const bodyHtml = rows.map((row: string) => {
      const cells = row.split('|').filter((c: string) => c.trim())
      return '<tr>' + cells.map((c: string) => `<td>${c.trim()}</td>`).join('') + '</tr>'
    }).join('')
    return `${pre}<table>${headerHtml}<tbody>${bodyHtml}</tbody></table>`
  })

  // 4. 标题
  html = html.replace(/^####\s+(.*)$/gm, '<h4>$1</h4>')
  html = html.replace(/^###\s+(.*)$/gm, '<h3>$1</h3>')
  html = html.replace(/^##\s+(.*)$/gm, '<h2>$1</h2>')
  html = html.replace(/^#\s+(.*)$/gm, '<h1>$1</h1>')

  // 5. 引用标记 [[page:123|标题]] → 链接（必须在代码块保护之后、链接语法之前）
  //    开源版使用 hash 路由，链接用 router.resolve 生成（如 #/page/123），避免硬编码绝对路径
  html = html.replace(/\[\[(page:(\d+)(?:\|([^\]]*))?|item:(\d+)(?:\|([^\]]*))?|item_list|user_center|messages|login|register|home)\]\]/g, (_match, tag, pageId, pageTitle, itemId, itemName) => {
    if (pageId) {
      const title = pageTitle || `页面 #${pageId}`
      const href = resolveAiHref('page', { page_id: Number(pageId) })
      return `<a href="${escapeAttr(href)}" target="_blank" rel="noopener noreferrer" class="ai-ref-link" data-kind="page" data-id="${pageId}">${escapeHtml(title)}</a>`
    }
    if (itemId) {
      const name = itemName || `项目 #${itemId}`
      const href = resolveAiHref('item', { item_id: Number(itemId) })
      return `<a href="${escapeAttr(href)}" target="_blank" rel="noopener noreferrer" class="ai-ref-link" data-kind="item" data-id="${itemId}">${escapeHtml(name)}</a>`
    }
    // 系统级引用
    const nameMap: Record<string, string> = {
      item_list: '项目列表', user_center: '个人中心',
      messages: '消息中心', login: '登录', register: '注册', home: '首页',
    }
    const href = resolveAiHref(tag, {})
    const name = nameMap[tag] || tag
    return `<a href="${escapeAttr(href)}" target="_blank" rel="noopener noreferrer" class="ai-ref-link" data-kind="${tag}">${escapeHtml(name)}</a>`
  })

  // 6. 图片 ![alt](url)（过滤危险协议）
  html = html.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, (_, alt, url) => {
    if (isDangerousUrl(url)) return ''
    return `<img src="${escapeAttr(url)}" alt="${escapeAttr(alt)}" style="max-width:100%;border-radius:4px" />`
  })

  // 7. 链接 [text](url)（过滤危险协议）
  html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (_, text, url) => {
    if (isDangerousUrl(url)) return text
    return `<a href="${escapeAttr(url)}" target="_blank" rel="noopener noreferrer">${text}</a>`
  })

  // 8. 粗体、斜体
  html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
  html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>')

  // 9. 无序列表
  html = html.replace(/((?:^[*-] .+$\n?)+)/gm, (block) => {
    const items = block.trim().split('\n').map((line: string) => `<li>${line.replace(/^[*-] /, '')}</li>`).join('')
    return `<ul>${items}</ul>`
  })

  // 10. 有序列表
  html = html.replace(/((?:^\d+\. .+$\n?)+)/gm, (block) => {
    const items = block.trim().split('\n').map((line: string) => `<li>${line.replace(/^\d+\. /, '')}</li>`).join('')
    return `<ol>${items}</ol>`
  })

  // 11. 换行（但不在块级元素内添加额外 br）
  html = html.replace(/\n/g, '<br>')

  // 12. 还原代码块
  html = html.replace(/\x00CODEBLOCK(\d+)\x00/g, (_, idx) => codeBlocks[parseInt(idx)])

  return sanitizeHtml(html)
}

/**
 * 增量 Markdown 渲染：
 * 策略：每次都对完整文本做 Markdown 渲染，但通过节流控制 DOM 更新频率，
 * 加上流式光标，实现视觉上的打字机效果。
 * 流结束时做一次最终完整渲染（无光标）确保结果正确。
 *
 * 为什么不用真正的增量 HTML 拼接？
 * 因为 Markdown → HTML 映射不是线性的（代码块、表格、列表会折叠多行），
 * 拼接会导致重叠/错位。完整渲染 + 节流是更稳健的方案。
 */
const STREAMING_CURSOR_HTML = '<span class="streaming-cursor"></span>'

/* ── 每个组件实例独立的 RAF 状态 ──
 * script setup 中顶层 const 如果直接赋值对象字面量，仍是模块级单例。
 * 用工厂函数确保每个组件实例创建独立对象。
 */
function createStreamState() {
  return {
    rafId: null as number | null,
    pendingContent: null as string | null,
    lastRenderedContent: '',
  }
}
const streamState = createStreamState()

/**
 * [Fix 流式渲染 v3] 最保守方案：流式期间不做任何剥离/暂缓，原文直接渲染。
 *
 * v3 原则：**任何字符都不扣不留不改**——流式中裸标记（半截 [[page:12、
 * 孤立 ` 等）可能短暂可见（闪烁），但结束后 finalize 用完整原文全量
 * 重渲染，最终显示保证正确。可见性瑕疵 << 内容丢失。
 * （配合后端流式正文占位回填，项目名在流式中也不会缺失。）
 */

function scheduleStreamUpdate(content: string) {
  streamState.pendingContent = content
  if (streamState.rafId !== null) return // 已有调度，等待下一帧
  streamState.rafId = requestAnimationFrame(() => {
    streamState.rafId = null
    if (!streamState.pendingContent) return
    const text = streamState.pendingContent
    streamState.pendingContent = null
    if (text === streamState.lastRenderedContent) return
    const html = renderMarkdown(text) + STREAMING_CURSOR_HTML
    streamState.lastRenderedContent = text
    applyHtmlToDom(html)
    addCodeBlockCopyButtons()
  })
}

/**
 * 完整渲染：流结束后或非流式消息，渲染最终结果（无光标）
 */
function fullRender(text: string): string {
  streamState.lastRenderedContent = text
  return renderMarkdown(text)
}

/** 将 HTML 写入 DOM（用于增量更新） */
function applyHtmlToDom(html: string) {
  const el = bubbleContentRef.value
  if (!el) return
  el.innerHTML = html
}

/** 流结束：做一次完整渲染（无光标） */
function finalizeContent(content: string) {
  isStreaming.value = false
  // 清除可能残留的 RAF
  if (streamState.rafId !== null) {
    cancelAnimationFrame(streamState.rafId)
    streamState.rafId = null
    streamState.pendingContent = null
  }
  const html = fullRender(content)
  applyHtmlToDom(html)
}

/** 转义 HTML 特殊字符（用于代码块内容和行内代码） */
function escapeHtml(str: string): string {
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

/** 转义 HTML 属性值 */
function escapeAttr(str: string): string {
  return str
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
}

/** 检查 URL 是否使用危险协议 */
function isDangerousUrl(url: string): boolean {
  const trimmed = url.trim().toLowerCase()
  return /^(javascript:|data:|vbscript:)/.test(trimmed)
}

const copyContent = async () => {
  const text = props.message.content || ''
  try {
    await navigator.clipboard.writeText(text)
  } catch {
    // clipboard API unavailable (non-HTTPS / non-localhost) — fallback to execCommand
    const textarea = document.createElement('textarea')
    textarea.value = text
    textarea.style.position = 'fixed'
    textarea.style.opacity = '0'
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
  }
  copySuccess.value = true
  setTimeout(() => { copySuccess.value = false }, 1500)
}

/** 给代码块添加浮动复制按钮 */
const bubbleContentRef = ref<HTMLElement | null>(null)

const addCodeBlockCopyButtons = () => {
  if (!bubbleContentRef.value) return
  const preElements = bubbleContentRef.value.querySelectorAll('pre')
  preElements.forEach((pre) => {
    if (pre.querySelector('.code-copy-btn')) return
    pre.style.position = 'relative'
    const btn = document.createElement('i')
    btn.className = 'fas fa-copy code-copy-btn'
    btn.title = '复制代码'
    btn.addEventListener('click', async (e) => {
      e.stopPropagation()
      const code = pre.querySelector('code')
      const text = code ? code.textContent : pre.textContent
      if (!text) return
      try {
        await navigator.clipboard.writeText(text)
      } catch {
        const textarea = document.createElement('textarea')
        textarea.value = text
        textarea.style.position = 'fixed'
        textarea.style.opacity = '0'
        document.body.appendChild(textarea)
        textarea.select()
        document.execCommand('copy')
        document.body.removeChild(textarea)
      }
      btn.className = 'fas fa-check code-copy-btn'
      btn.title = '已复制'
      setTimeout(() => {
        btn.className = 'fas fa-copy code-copy-btn'
        btn.title = '复制代码'
      }, 1500)
    })
    pre.appendChild(btn)
  })
}

// ─── 流式内容更新（打字机效果） ───────────────────────
watch(() => props.message.content, (newContent) => {
  if (!newContent) return
  if (isStreaming.value) {
    // 流式中：节流渲染 + 闪烁光标
    nextTick(() => {
      scheduleStreamUpdate(newContent)
    })
  } else {
    // 非流式（历史消息或已结束的消息）：直接完整渲染
    const html = fullRender(newContent)
    nextTick(() => {
      applyHtmlToDom(html)
      addCodeBlockCopyButtons()
    })
  }
})

// ─── 流状态变化：开始 → 结束 ────────────────────────────
watch(() => props.isLoading, (loading, wasLoading) => {
  if (wasLoading && !loading) {
    // 流刚结束，做一次完整渲染确保正确
    const content = props.message.content
    if (content) {
      nextTick(() => {
        finalizeContent(content)
        addCodeBlockCopyButtons()
      })
    }
  } else if (loading) {
    // 流开始
    isStreaming.value = true
    streamState.lastRenderedContent = ''
  }
}, { immediate: true })

// ─── [Fix 流式渲染] done 事件驱动的最终渲染 ───────────
// 父组件在 SSE done/错误/用户停止后递增 finalizeTick，
// 此处显式触发完整重渲染，不依赖 isLoading/content watch 巧合，
// 确保流式期间的半截标记闭合上屏、半渲染 HTML 不残留。
watch(() => props.finalizeTick, (tick, oldTick) => {
  if (tick === oldTick) return
  const content = props.message.content
  if (!content) return
  nextTick(() => {
    finalizeContent(content)
    addCodeBlockCopyButtons()
  })
})

// ─── 初始渲染（非流式消息的首次挂载） ─────────────────
onMounted(() => {
  const content = props.message.content
  if (content && !props.isLoading) {
    const html = fullRender(content)
    applyHtmlToDom(html)
  }
  addCodeBlockCopyButtons()
})

onUnmounted(() => {
  // 清理残留的 RAF
  if (streamState.rafId !== null) {
    cancelAnimationFrame(streamState.rafId)
    streamState.rafId = null
  }
})
</script>

<style scoped lang="scss">
.ai-message-bubble {
  display: flex;
  flex-direction: column;
  margin-bottom: 16px;

  &.user {
    align-items: flex-end;
  }
}

.bubble-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--color-grey);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-bottom: 4px;
  align-self: flex-start;

  i {
    color: #fff;
    font-size: 12px;
  }
}

.user .bubble-avatar {
  background: var(--color-active);
  align-self: flex-end;
}

.bubble-body {
  max-width: 100%;
  min-width: 0;
  width: 100%;
}

.user .bubble-body {
  max-width: 90%;
  width: auto;
}
.bubble-content {
  background: var(--color-bg-primary);
  padding: 9px 14px;
  border-radius: 8px;
  box-shadow: var(--shadow-xs);
  border: 1px solid var(--color-border);
  line-height: 1.5;
  word-wrap: break-word;
  overflow-x: auto;
  font-size: 13px;

  [data-theme='dark'] & {
    background: var(--color-bg-primary);
    border-color: var(--color-border);
  }

  :deep(pre) {
    background: var(--color-bg-secondary);
    padding: 8px 10px;
    border-radius: 6px;
    overflow-x: auto;
    border: 1px solid var(--color-border);
    margin: 6px 0;

    [data-theme='dark'] & {
      background: var(--color-bg-secondary);
      border-color: var(--color-border);
    }
  }

  :deep(code) {
    font-size: 12px;
  }

  /* 代码块浮动复制按钮（JS 动态注入） */
  :deep(.code-copy-btn) {
    position: absolute;
    top: 6px;
    right: 6px;
    cursor: pointer;
    font-size: 13px;
    color: var(--color-text-secondary);
    opacity: 0.6;
    transition: opacity 0.15s;
    padding: 4px;
    z-index: 1;

    &:hover {
      opacity: 1;
    }
  }

  :deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 8px 0;
    font-size: 13px;
  }

  :deep(a) {
    color: var(--color-active);
    text-decoration: none;
    &:hover { text-decoration: underline; }
  }

  :deep(.ai-ref-link) {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 1px 6px;
    border-radius: 4px;
    background: var(--color-bg-secondary);
    border: 1px solid var(--color-border);
    font-size: 12px;
    color: var(--color-active);
    text-decoration: none;
    white-space: nowrap;
    &:hover {
      text-decoration: none;
      border-color: var(--color-active);
      background: var(--color-bg-secondary);
    }
  }

  :deep(blockquote) {
    margin: 8px 0;
    padding: 6px 12px;
    border-left: 3px solid var(--color-active);
    background: var(--color-bg-secondary);
    color: var(--color-text-secondary);
    border-radius: 0 4px 4px 0;
  }

  :deep(table th),
  :deep(table td) {
    padding: 6px 10px;
    border: 1px solid var(--color-border);
    text-align: left;
  }

  :deep(table thead) {
    background: var(--color-bg-secondary);
  }
}

.user .bubble-content {
  background: var(--color-bg-secondary);
  color: var(--color-primary);
  border: 1px solid var(--color-border);
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 14px;
  background: var(--color-bg-primary);
  border-radius: 8px;
  border: 1px solid var(--color-border);
  font-size: 13px;
  color: var(--color-text-secondary);
}

.status-text {
  font-size: 13px;
}

.typing-indicator {
  display: flex;
  gap: 4px;
  padding: 12px 14px;
  background: var(--color-bg-primary);
  border-radius: 8px;
  border: 1px solid var(--color-border);
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--color-text-secondary);
  animation: typing 1.4s infinite;

  &:nth-child(2) { animation-delay: 0.2s; }
  &:nth-child(3) { animation-delay: 0.4s; }
}

@keyframes typing {
  0%, 60%, 100% { transform: translateY(0); opacity: 0.7; }
  30% { transform: translateY(-10px); opacity: 1; }
}

.bubble-refs {
  margin-top: 6px;
  padding: 6px 10px;
  background: var(--color-bg-secondary);
  border-radius: 6px;
  font-size: 12px;
}

.refs-label {
  color: var(--color-text-secondary);
  margin-bottom: 4px;
  font-weight: 500;
}

.ref-link {
  display: flex;
  align-items: center;
  gap: 4px;
  color: var(--color-active);
  padding: 3px 0;
  text-decoration: none;
  cursor: pointer;
  &:hover { text-decoration: underline; }

  &.ref-link-static {
    color: var(--color-text-secondary);
    cursor: default;
    &:hover { text-decoration: none; }
  }
}

.bubble-actions {
  display: flex;
  gap: 8px;
  margin-top: 6px;
  padding: 0 4px;
}

.action-btn {
  font-size: 12px;
  color: var(--color-text-secondary);
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all 0.15s;

  &:hover {
    color: var(--color-active);
    background: var(--color-bg-secondary);
  }

  &.active {
    color: var(--color-active);
  }
}

/* ─── 流式打字光标 ─── */
:deep(.streaming-cursor) {
  display: inline-block;
  width: 2px;
  height: 1em;
  vertical-align: text-bottom;
  margin-left: 2px;
  background: var(--color-text-primary, currentColor);
  border-radius: 1px;
  animation: cursor-blink 0.8s steps(2) infinite;
}

@keyframes cursor-blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0; }
}

/* ─── 移动端适配 ─── */
@media (max-width: 768px) {
  /* assistant 气泡收缩到 85% */
  .bubble-body {
    max-width: 85%;
  }

  /* 用户消息气泡放宽到 95% */
  .user .bubble-body {
    max-width: 95%;
  }

  /* 操作按钮触摸区 ≥ 44px */
  .action-btn {
    font-size: 16px;
    padding: 12px;
    margin-right: 2px;
    min-width: 44px;
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .bubble-actions {
    gap: 2px;
  }

  /* 代码块和表格横向滚动 */
  .bubble-content {
    :deep(pre) {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      max-width: 100%;
    }

    :deep(table) {
      display: block;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      max-width: 100%;
      white-space: nowrap;
    }
  }

  /* 消息间距略减 */
  .ai-message-bubble {
    margin-bottom: 12px;
  }
}
</style>
