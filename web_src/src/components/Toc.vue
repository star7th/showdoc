<template>
  <div class="toc-container" v-if="tocItems.length > 0 || isGenerating">
    <div class="toc-toggle" @click="toggleToc">
      <i class="fas fa-list"></i>
    </div>
    <div class="toc-list" :class="{ 'is-open': isOpen }">
      <div class="toc-header">
        <span>{{ $t('page.catalog') }}</span>
        <i class="fas fa-times" @click="toggleToc"></i>
      </div>
      <div class="toc-content">
        <div
          v-for="(item, index) in tocItems"
          :key="index"
          :class="['toc-item', `toc-level-${item.level}`, { 'is-active': activeId === item.id }]"
          @click="scrollToHeading(item.id)"
        >
          <span>{{ item.text }}</span>
        </div>
        <div v-if="tocItems.length === 0 && !isGenerating" class="toc-empty">
          {{ $t('page.no_catalog') }}
        </div>
        <div v-if="isGenerating" class="toc-loading">
          <a-spin size="small" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'

interface TocItem {
  id: string
  text: string
  level: number
}

const isOpen = ref(false)
const activeId = ref('')
const tocItems = ref<TocItem[]>([])
const isGenerating = ref(true)

// 从 Markdown 内容中提取标题生成目录
const generateToc = () => {
  isGenerating.value = true

  // 尝试多个可能的选择器（支持 Editormd）
  const selectors = [
    // Editormd 预览容器
    '.editormd-preview-container h1, .editormd-preview-container h2, .editormd-preview-container h3, .editormd-preview-container h4, .editormd-preview-container h5, .editormd-preview-container h6',
    '.editormd-preview h1, .editormd-preview h2, .editormd-preview h3, .editormd-preview h4, .editormd-preview h5, .editormd-preview h6',
    '.markdown-body h1, .markdown-body h2, .markdown-body h3, .markdown-body h4, .markdown-body h5, .markdown-body h6',
    // 页面内容主容器（备用）
    '#page-content-main h1, #page-content-main h2, #page-content-main h3, #page-content-main h4, #page-content-main h5, #page-content-main h6'
  ]

  let headings: NodeListOf<Element> | null = null

  for (const selector of selectors) {
    headings = document.querySelectorAll(selector)
    if (headings.length > 0) {
      break
    }
  }

  if (!headings || headings.length === 0) {
    headings = document.querySelectorAll('.editormd-preview-container h1, .editormd-preview-container h2, .editormd-preview-container h3, .editormd-preview-container h4, .editormd-preview-container h5, .editormd-preview-container h6')
  }

  tocItems.value = []

  headings.forEach((heading) => {
    let id = heading.getAttribute('id')

    // 如果标题没有 ID，生成一个
    if (!id) {
      const level = heading.tagName.charAt(1)
      id = `heading-${level}-${Math.random().toString(36).substr(2, 9)}`
      heading.setAttribute('id', id)
    }

    const text = heading.textContent || ''
    const level = parseInt(heading.tagName.charAt(1))

    if (id && text) {
      tocItems.value.push({
        id,
        text,
        level
      })
    }
  })

  isGenerating.value = false

  // 如果有标题，默认展开目录
  if (tocItems.value.length > 0) {
    isOpen.value = true
  }

  // 如果没有找到标题但应该有，可能 Vditor 还在渲染，继续重试
  if (tocItems.value.length === 0) {
    // 检查不同的容器
    const containers = [
      document.querySelector('.vditor-reset'),
      document.querySelector('.vditor-preview'),
      document.querySelector('#page-content-main')
    ]

    for (const container of containers) {
      if (container && container.innerHTML.trim()) {
        break
      }
    }
  }
}

// 滚动到指定标题
const scrollToHeading = (id: string) => {
  const element = document.getElementById(id)
  if (element) {
    const offset = 120
    const elementPosition = element.getBoundingClientRect().top
    const offsetPosition = elementPosition + window.pageYOffset - offset

    window.scrollTo({
      top: offsetPosition,
      behavior: 'smooth'
    })
  }
}

// 切换目录显示
const toggleToc = () => {
  isOpen.value = !isOpen.value
}

// 监听滚动事件，更新激活状态
const handleScroll = () => {
  // 尝试多个可能的选择器（同时支持 Vditor 和 Editormd）
  const selectors = [
    // Editormd 预览容器（优先）
    '.editormd-preview-container h1, .editormd-preview-container h2, .editormd-preview-container h3, .editormd-preview-container h4, .editormd-preview-container h5, .editormd-preview-container h6',
    '.editormd-preview h1, .editormd-preview h2, .editormd-preview h3, .editormd-preview h4, .editormd-preview h5, .editormd-preview h6',
    '.markdown-body h1, .markdown-body h2, .markdown-body h3, .markdown-body h4, .markdown-body h5, .markdown-body h6',
    // Vditor 编辑器（兼容旧版）
    '.vditor-reset h1, .vditor-reset h2, .vditor-reset h3, .vditor-reset h4, .vditor-reset h5, .vditor-reset h6',
    '.vditor-preview h1, .vditor-preview h2, .vditor-preview h3, .vditor-preview h4, .vditor-preview h5, .vditor-preview h6',
    // 页面内容主容器（备用）
    '#page-content-main h1, #page-content-main h2, #page-content-main h3, #page-content-main h4, #page-content-main h5, #page-content-main h6'
  ]

  let headings: NodeListOf<Element> | null = null

  for (const selector of selectors) {
    headings = document.querySelectorAll(selector)
    if (headings.length > 0) {
      break
    }
  }

  if (!headings || headings.length === 0) {
    headings = document.querySelectorAll('.editormd-preview-container h1, .editormd-preview-container h2, .editormd-preview-container h3, .editormd-preview-container h4, .editormd-preview-container h5, .editormd-preview-container h6')
  }

  const scrollPosition = window.pageYOffset + 150

  let activeElement: any = null
  headings.forEach((heading) => {
    const headingPosition = heading.getBoundingClientRect().top + window.pageYOffset

    if (headingPosition <= scrollPosition) {
      activeElement = heading
    }
  })

  if (activeElement) {
    activeId.value = activeElement.getAttribute('id') || ''
  }
}

// 使用 MutationObserver 监听内容变化
let observer: MutationObserver | null = null

onMounted(() => {
  // 多次尝试生成 TOC，确保捕获到 Vditor 渲染的标题
  const tryGenerateToc = (delay: number) => {
    setTimeout(() => {
      generateToc()
    }, delay)
  }

  // 尝试多次生成
  tryGenerateToc(500)
  tryGenerateToc(1000)
  tryGenerateToc(1500)
  tryGenerateToc(2000)
  tryGenerateToc(3000)

  // 监听滚动事件
  window.addEventListener('scroll', handleScroll, { passive: true })

  // 监听 DOM 变化
  observer = new MutationObserver(() => {
    setTimeout(() => {
      generateToc()
    }, 300)
  })

  // 延迟观察，确保元素存在
  setTimeout(() => {
    // 尝试找到内容容器（优先 Editormd，其次 Vditor）
    const contentContainers = [
      '.editormd-preview-container',
      '.editormd-preview',
      '.markdown-body',
      '.vditor-reset',
      '.vditor-preview',
      '#page-content-main'
    ]

    let content: Element | null = null
    for (const selector of contentContainers) {
      content = document.querySelector(selector)
      if (content && content.innerHTML.trim()) {
        break
      }
    }

    if (content && observer) {
      observer.observe(content, {
        childList: true,
        subtree: true
      })
    } else {
      // 如果还没找到，再次尝试
      setTimeout(() => {
        for (const selector of contentContainers) {
          content = document.querySelector(selector)
          if (content && content.innerHTML.trim()) {
            break
          }
        }
        if (content && observer) {
          observer.observe(content, {
            childList: true,
            subtree: true
          })
        }
      }, 1000)
    }
  }, 500)

  // 监听图片加载完成
  setTimeout(() => {
    // 尝试找到内容容器（优先 Editormd，其次 Vditor）
    const contentContainers = [
      '.editormd-preview-container',
      '.editormd-preview',
      '.markdown-body',
      '.vditor-reset',
      '.vditor-preview',
      '#page-content-main'
    ]

    let content: Element | null = null
    for (const selector of contentContainers) {
      content = document.querySelector(selector)
      if (content) {
        break
      }
    }

    if (content) {
      const images = content.querySelectorAll('img')
      images.forEach((img) => {
        const imageEl = img as HTMLImageElement
        if (imageEl.complete) {
          generateToc()
        } else {
          imageEl.addEventListener('load', generateToc)
        }
      })
    }
  }, 800)
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll)

  if (observer) {
    observer.disconnect()
  }
})
</script>

<style lang="scss" scoped>
.toc-container {
  position: relative;
  z-index: 100;
  width: 100%;
}

.toc-toggle {
  width: 40px;
  height: 40px;
  background-color: var(--color-bg-primary);
  border: 1px solid var(--color-border);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: var(--shadow-xs);
  transition: all 0.15s ease;

  i {
    color: var(--color-active);
    font-size: 16px;
    transition: color 0.15s ease;
  }

  &:hover {
    box-shadow: var(--shadow-sm);
    border-color: var(--color-active);
    background: var(--hover-overlay);
    
    [data-theme="dark"] & {
      box-shadow: var(--shadow-sm);
    }
    
    i {
      color: var(--color-active);
    }
  }
}

.toc-list {
  position: absolute;
  top: 50px;
  left: 0;
  width: 220px;
  max-height: calc(100vh - 250px);
  background-color: var(--color-bg-primary);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-10px);
  transition: all 0.15s ease;
}

.toc-list.is-open {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.toc-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid var(--color-border);
  background: rgba(0, 123, 255, 0.05);
  font-weight: 600;
  color: var(--color-text-primary);
  
  [data-theme="dark"] & {
    background: rgba(74, 158, 255, 0.08);
  }
  
  span {
    color: var(--color-active);
  }

  i {
    cursor: pointer;
    color: var(--color-text-secondary);
    transition: color 0.15s ease;

    &:hover {
      color: var(--color-red);
    }
  }
}

.toc-content {
  padding: 8px 0;
  max-height: calc(100vh - 320px);
  overflow-y: auto;
  
  // 滚动条样式已移至全局样式（styles/index.scss）
  // 所有滚动条统一使用全局样式
}

.toc-item {
  padding: 8px 12px;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--color-text-primary);
  font-size: 13px;
  border-radius: 4px;
  margin: 2px 8px;
  position: relative;

  &:hover {
    background-color: rgba(0, 123, 255, 0.08);
    color: var(--color-active);
    
    [data-theme="dark"] & {
      background-color: rgba(74, 158, 255, 0.12);
    }
  }

  &.is-active {
    background: rgba(0, 123, 255, 0.12);
    color: var(--color-active);
    font-weight: 500;
    
    [data-theme="dark"] & {
      background: rgba(74, 158, 255, 0.15);
    }
    
    // 左侧指示条
    &::before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 3px;
      height: 16px;
      background: var(--color-active);
      border-radius: 0 2px 2px 0;
    }
  }

  // H1 标题
  &.toc-level-1 {
    padding-left: 12px;
    font-weight: 600;
    font-size: 14px;
  }

  // H2 标题
  &.toc-level-2 {
    padding-left: 20px;
    font-weight: 500;
  }

  // H3 标题
  &.toc-level-3 {
    padding-left: 28px;
    font-size: 12px;
  }

  // H4 及以下
  &.toc-level-4 {
    padding-left: 36px;
    font-size: 12px;
    opacity: 0.8;
  }

  &.toc-level-5 {
    padding-left: 44px;
    font-size: 12px;
    opacity: 0.7;
  }

  &.toc-level-6 {
    padding-left: 52px;
    font-size: 12px;
    opacity: 0.6;
  }
}

.toc-empty {
  padding: 20px;
  text-align: center;
  color: var(--color-text-secondary);
  font-size: 13px;
}

.toc-loading {
  padding: 20px;
  text-align: center;
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>

