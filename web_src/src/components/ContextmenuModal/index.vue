<template>
  <div class="contextmenu-modal" :class="{ show: show }" @click.stop="closeHandle" @dblclick.stop="closeHandle"
    @contextmenu.stop="closeHandle">
    <div class="container" ref="contextmenuRef" :style="{ top: `${top}px`, left: `${left}px` }" @click.stop @scroll="handleScroll">
      <div class="column">
        <div class="contextmenu-item bgColor-select" v-for="(item, index) in useList" :key="index" v-show="!item.hidden"
          @click="clickHandle(item)" @dblclick.stop @contextmenu.stop 
          :ref="el => setItemRef(el as HTMLElement, index)"
          @mouseenter="() => handleItemMouseEnter(index)"
          @mouseleave="() => handleItemMouseLeave(index)">
          <div class="line-container text-default">
            <div class="item-container">
              <img class="img" v-if="item.img" :src="item.img">
              <div class="icon" v-if="item.icon">
                <i :class="getIconClass(item.icon)"></i>
              </div>
              <div class="text">{{ item.text }}</div>
              <!-- 使用 teleport 将子菜单渲染到 body，避免滚动影响 -->
              <teleport to="body" v-if="item.children && item.children.length">
                <Children 
                  :show="activeSubmenuIndex === index" 
                  :itemRef="itemRefs.get(index)" 
                  :scrollOffset="scrollOffset"
                  :list="item.children" 
                  @close="closeHandle"
                  @mouseenter="() => handleSubmenuMouseEnter(index)"
                  @mouseleave="() => handleSubmenuMouseLeave(index)" />
              </teleport>
            </div>

            <div class="shortcut text-secondary" v-if="item.shortcut && !item.children">
              {{ formatShortcut(item.shortcut) }}
            </div>
            <div class="checked" v-if="item.checked">
              <i class="fas fa-check"></i>
            </div>
            <div class="arrow text-secondary" v-if="item.children && item.children.length">
              <i class="fas fa-angle-right"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import type { PropType } from 'vue'
import type { ContextmenuModalItemInterface } from './index'
import Children from './Children.vue'
import { getIconClass } from '@/utils/icon'

const props = defineProps({
  x: Number,
  y: Number,
  list: Object as PropType<ContextmenuModalItemInterface[]>,
  onCancel: Function,
})

const show = ref(false)
const contextmenuRef = ref()
const top = ref(0)
const left = ref(0)
const width = ref(0)
const scrollOffset = ref(0)
const itemRefs = new Map<number, HTMLElement>()
const activeSubmenuIndex = ref<number | null>(null)
let submenuHoverTimer: number | null = null

// 设置菜单项引用
function setItemRef(el: HTMLElement | null, index: number) {
  if (el) {
    itemRefs.set(index, el)
  } else {
    itemRefs.delete(index)
  }
}

// 鼠标进入菜单项
function handleItemMouseEnter(index: number) {
  if (submenuHoverTimer) {
    clearTimeout(submenuHoverTimer)
    submenuHoverTimer = null
  }
  submenuHoverTimer = window.setTimeout(() => {
    activeSubmenuIndex.value = index
  }, 200)
}

// 鼠标离开菜单项
function handleItemMouseLeave(index: number) {
  if (submenuHoverTimer) {
    clearTimeout(submenuHoverTimer)
    submenuHoverTimer = null
  }
  submenuHoverTimer = window.setTimeout(() => {
    if (activeSubmenuIndex.value === index) {
      activeSubmenuIndex.value = null
    }
  }, 100)
}

// 鼠标进入子菜单
function handleSubmenuMouseEnter(index: number) {
  if (submenuHoverTimer) {
    clearTimeout(submenuHoverTimer)
    submenuHoverTimer = null
  }
  activeSubmenuIndex.value = index
}

// 鼠标离开子菜单
function handleSubmenuMouseLeave(index: number) {
  if (submenuHoverTimer) {
    clearTimeout(submenuHoverTimer)
    submenuHoverTimer = null
  }
  submenuHoverTimer = window.setTimeout(() => {
    if (activeSubmenuIndex.value === index) {
      activeSubmenuIndex.value = null
    }
  }, 100)
}

// 自动为菜单项分配快捷键
function autoAssignShortcuts(list: ContextmenuModalItemInterface[]): ContextmenuModalItemInterface[] {
  const assignedKeys = new Set<string>()
  const result: ContextmenuModalItemInterface[] = []
  
  // 第一遍：为已定义快捷键的项标记已使用的键
  list.forEach(item => {
    if (item.shortcut && !item.children) {
      const key = item.shortcut.toLowerCase().replace(/^ctrl\+|^shift\+|^alt\+/g, '')
      if (/^[a-z]$/.test(key)) {
        assignedKeys.add(key)
      }
    }
  })
  
  // 第二遍：为没有快捷键的项自动分配
  list.forEach(item => {
    const newItem = { ...item }
    
    // 跳过有子菜单的项
    if (item.children && item.children.length > 0) {
      result.push(newItem)
      return
    }
    
    // 如果已有快捷键，直接使用
    if (item.shortcut) {
      result.push(newItem)
      return
    }
    
    // 尝试使用文本中的第一个英文字母
    let assignedKey: string | null = null
    
    // 提取文本中的第一个英文字母
    const text = item.text || ''
    const firstLetterMatch = text.match(/[a-zA-Z]/)
    
    if (firstLetterMatch) {
      const lowerKey = firstLetterMatch[0].toLowerCase()
      if (!assignedKeys.has(lowerKey)) {
        assignedKey = lowerKey
        assignedKeys.add(lowerKey)
      }
    }
    
    // 如果第一个字母不可用，按顺序分配
    if (!assignedKey) {
      for (let i = 0; i < 26; i++) {
        const key = String.fromCharCode(97 + i) // a-z
        if (!assignedKeys.has(key)) {
          assignedKey = key
          assignedKeys.add(key)
          break
        }
      }
    }
    
    // 如果找到了可用的键，分配快捷键
    if (assignedKey) {
      newItem.shortcut = assignedKey
    }
    
    result.push(newItem)
  })
  
  return result
}

const useList = computed(() => {
  const list = props.list || []
  const filtered = list.filter(v => !v.hidden)
  return autoAssignShortcuts(filtered)
})

// 格式化快捷键显示文本
function formatShortcut(shortcut: string): string {
  // 将 Ctrl 替换为平台特定的显示
  // Windows/Linux 显示 Ctrl，macOS 显示 ⌘ (但当前统一使用 Ctrl)
  return shortcut
    .replace(/Ctrl\+/gi, 'Ctrl+')
    .replace(/Shift\+/gi, 'Shift+')
    .replace(/Alt\+/gi, 'Alt+')
    .replace(/Delete/gi, 'Del')
    .replace(/Enter/gi, 'Enter')
}

// 解析快捷键字符串，返回匹配函数
function parseShortcut(shortcut: string): (event: KeyboardEvent) => boolean {
  const parts = shortcut.toLowerCase().split('+').map(s => s.trim())
  const key = parts[parts.length - 1]
  const hasCtrl = parts.includes('ctrl')
  const hasShift = parts.includes('shift')
  const hasAlt = parts.includes('alt')

  return (event: KeyboardEvent) => {
    // 如果快捷键是单个字母，只匹配字母键，不要求修饰键
    const isSingleLetter = /^[a-z]$/.test(key)
    
    if (isSingleLetter) {
      // 单个字母键：直接匹配字母（大小写不敏感），不要求修饰键
      const eventKey = event.key.toLowerCase()
      // 确保是字母键，且不按下修饰键（除非明确指定）
      if (eventKey === key.toLowerCase()) {
        // 如果快捷键定义中没有指定修饰键，则要求不按下任何修饰键
        if (!hasCtrl && !hasShift && !hasAlt) {
          return !event.ctrlKey && !event.shiftKey && !event.altKey && !event.metaKey
        }
        // 如果快捷键定义中指定了修饰键，则按定义匹配
        return (!hasCtrl || event.ctrlKey) &&
               (!hasShift || event.shiftKey) &&
               (!hasAlt || event.altKey)
      }
      return false
    }
    
    // 组合键：需要完全匹配
    const eventKey = event.key.toLowerCase()
    const shortcutKey = key.toLowerCase()
    
    // 处理 Delete 键的多种表示方式（event.key 可能是 "Delete"）
    let keyMatch = false
    if (shortcutKey === 'delete' || shortcutKey === 'del') {
      keyMatch = eventKey === 'delete' || eventKey === 'del'
    } else {
      keyMatch = eventKey === shortcutKey
    }
    
    return (
      keyMatch &&
      event.ctrlKey === hasCtrl &&
      event.shiftKey === hasShift &&
      event.altKey === hasAlt
    )
  }
}

// 处理键盘事件
function handleKeyDown(event: KeyboardEvent) {
  if (!show.value) return

  // 忽略修饰键单独按下
  if (event.key === 'Control' || event.key === 'Shift' || event.key === 'Alt' || event.key === 'Meta') {
    return
  }

  // 查找匹配的菜单项
  for (const item of useList.value) {
    if (item.shortcut && !item.children && item.onclick) {
      const matcher = parseShortcut(item.shortcut)
      if (matcher(event)) {
        event.preventDefault()
        event.stopPropagation()
        clickHandle(item)
        return
      }
    }
  }
}

onMounted(() => {
  const clientWidth = document.body.clientWidth || window.innerWidth
  const clientHeight = document.body.clientHeight || window.innerHeight
  let tempTop = props.y || 0
  let tempLeft = props.x || 0
  if (contextmenuRef.value) {
    if (contextmenuRef.value.clientHeight + tempTop > clientHeight - 5) tempTop = clientHeight - contextmenuRef.value.clientHeight - 5
    if (contextmenuRef.value.clientWidth + tempLeft > clientWidth - 5) tempLeft = clientWidth - contextmenuRef.value.clientWidth - 5
    width.value = contextmenuRef.value.clientWidth
  }
  top.value = tempTop
  left.value = tempLeft
  setTimeout(() => {
    show.value = true
  })

  // 添加键盘事件监听
  document.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  // 移除键盘事件监听
  document.removeEventListener('keydown', handleKeyDown)
  
  // 清理定时器
  if (submenuHoverTimer) {
    clearTimeout(submenuHoverTimer)
    submenuHoverTimer = null
  }
})

function clickHandle(item: ContextmenuModalItemInterface) {
  if (item.children) return
  closeHandle()
  item.onclick && item.onclick()
}

function closeHandle() {
  show.value = false
  setTimeout(() => {
    props.onCancel?.()
  }, 100)
}

// 处理滚动事件，更新子菜单位置
function handleScroll(event: Event) {
  const target = event.target as HTMLElement
  scrollOffset.value = target.scrollTop
}
</script>
<style lang="scss" scoped>
.contextmenu-modal {
  position: fixed;
  top: 0;
  left: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  opacity: 0;
  transition: opacity 100ms ease-in-out;
  z-index: 1000;
  user-select: none;
  -webkit-app-region: no-drag;

  &.show {
    opacity: 1;

    .container {
      transform: scale(1);
    }
  }
}


.container {
  position: absolute;
  max-height: 80vh;
  background-color: var(--color-default);
  border: 1px solid var(--color-interval);
  border-radius: 8px;
  box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.1);
  transform-origin: left top;
  transform: scale(0.3);
  transition: transform 100ms ease-in-out;
  overflow-y: auto;
  z-index: 1;

  &::-webkit-scrollbar {
    width: 6px;
  }

  &::-webkit-scrollbar-track {
    background: transparent;
  }

  &::-webkit-scrollbar-thumb {
    background: var(--color-interval);
    border-radius: 3px;
  }

  &::-webkit-scrollbar-thumb:hover {
    background: var(--color-border);
  }
}


.column {
  max-height: 80vh;
}

.contextmenu-item {
  display: flex;
  align-items: center;
  padding: 0 10px;
  cursor: pointer;
  overflow: unset;

  &:first-of-type {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;

    &::after {
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
    }
  }

  &:last-of-type {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;

    &::after {
      border-bottom-left-radius: 8px;
      border-bottom-right-radius: 8px;
    }
  }

  &:last-of-type .line-container {
    border: none;
  }

  .line-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    border-bottom: 1px solid var(--color-interval);
  }

  .item-container {
    display: flex;
    align-items: center;
    gap: 0.6em;
    height: 50px;
    padding: 0 10px 0 5px;

    .img {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
    }

    .icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 20px;
      height: 20px;
      flex-shrink: 0;
      font-size: 14px;
      color: var(--color-text-secondary); /* 默认统一灰色 */
      
      // 只保留删除操作的红色警告 - 简洁不花哨
      i.fa-trash-can,
      i.fa-trash {
        color: #ef4444;
        
        [data-theme="dark"] & {
          color: #f87171;
        }
      }
    }

    .text {
      white-space: nowrap;
    }
  }


  .shortcut {
    font-size: 12px;
    margin: 0 10px;
    opacity: 0.6;
  }

  .checked {
    font-size: 12px;
    margin: 0 10px;
    flex-shrink: 0;
  }

  .arrow {
    font-size: 12px;
    margin: 0 10px;
    flex-shrink: 0;
  }
}

// 暗黑主题适配
.contextmenu-modal {
  [data-theme="dark"] & {
    .icon,
    .checked,
    .arrow {
      color: var(--color-text-primary);
    }
  }
}
</style>

