<template>
  <div 
    ref="containerRef"
    class="common-dropdown-menu"
    @mouseenter="handleContainerEnter"
    @mouseleave="handleContainerLeave"
  >
    <!-- 触发按钮 - 支持插槽自定义 -->
    <div 
      class="trigger-button"
      @click="trigger === 'click' && handleToggleMenu()"
    >
      <slot>
        <!-- 默认的更多按钮 -->
        <div class="default-trigger">
          <i class="fas fa-ellipsis"></i>
        </div>
      </slot>
    </div>
    
    <!-- 下拉菜单 - 通过 Teleport 渲染到 body，避免被父元素裁剪 -->
    <Teleport to="body">
      <div 
        v-if="visible" 
        class="dropdown-menu-container"
        :class="`placement-${props.placement}`"
        :style="menuStyle"
        @mouseenter="handleMenuEnter"
        @mouseleave="handleMenuLeave"
      >
        <div class="dropdown-column">
          <div 
            class="dropdown-item" 
            v-for="(item, index) in list" 
            :key="index"
            @click="handleItemClick(item)"
          >
            <div class="item-content">
              <div class="item-icon" v-if="item.icon">
                <i :class="getIconClass(item.icon)"></i>
              </div>
              <div class="item-text">{{ item.text }}</div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
    
    <!-- 点击模式下的遮罩层 -->
    <Teleport to="body">
      <div 
        v-if="visible && trigger === 'click'" 
        class="dropdown-overlay"
        @click="handleClose"
      />
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { getIconClass } from '@/utils/icon'

// 菜单项接口
export interface DropdownMenuItem {
  icon?: string[]
  text: string
  value: string
}

// Props
interface Props {
  list: DropdownMenuItem[]
  trigger?: 'hover' | 'click'
  placement?: 'top' | 'bottom' | 'right'
  offsetX?: number // 左边偏移，正数向右，负数向左
}

const props = withDefaults(defineProps<Props>(), {
  trigger: 'hover',
  placement: 'top',
  offsetX: 0
})

// Emits
const emit = defineEmits<{
  select: [item: DropdownMenuItem]
}>()

// Refs
const visible = ref(false)
const containerRef = ref<HTMLElement | null>(null)
const menuPosition = ref({ top: 0, left: 0 })
let hideTimer: number | null = null

// 标记鼠标位置状态（用于 hover 模式）
let isMouseInContainer = false
let isMouseInMenu = false

// Computed
const menuStyle = computed(() => {
  if (props.placement === 'right') {
    return {
      top: `${menuPosition.value.top}px`,
      left: `${menuPosition.value.left}px`,
      transform: 'translateY(-50%)'
    } as any
  }

  let translateY = '-100%'
  if (props.placement === 'bottom') {
    translateY = '0'
  }
  return {
    top: `${menuPosition.value.top}px`,
    left: `${menuPosition.value.left}px`,
    transform: `translateX(-50%) translateY(${translateY})`,
    '--dropdown-translate-y': translateY
  } as any
})

// Methods
const updateMenuPosition = () => {
  if (!containerRef.value) return

  const rect = containerRef.value.getBoundingClientRect()

  if (props.placement === 'right') {
    // 在右侧展开，以触发器的垂直中心为基准
    menuPosition.value = {
      top: rect.top + rect.height / 2,
      left: rect.right + 8 + props.offsetX
    }
  } else if (props.placement === 'top') {
    menuPosition.value = {
      top: rect.top - 8,
      left: rect.left + rect.width / 2 + props.offsetX
    }
  } else {
    menuPosition.value = {
      top: rect.bottom + 8,
      left: rect.left + rect.width / 2 + props.offsetX
    }
  }
}

const showMenu = () => {
  if (hideTimer) {
    clearTimeout(hideTimer)
    hideTimer = null
  }
  updateMenuPosition()
  visible.value = true
}

const hideMenu = () => {
  // 只有当鼠标不在容器和菜单上时才隐藏
  if (!isMouseInContainer && !isMouseInMenu) {
    visible.value = false
  }
}

const scheduleHide = () => {
  if (hideTimer) {
    clearTimeout(hideTimer)
  }
  hideTimer = window.setTimeout(hideMenu, 100)
}

// 容器的 hover 事件
const handleContainerEnter = () => {
  if (props.trigger !== 'hover') return
  isMouseInContainer = true
  showMenu()
}

const handleContainerLeave = () => {
  if (props.trigger !== 'hover') return
  isMouseInContainer = false
  scheduleHide()
}

// 菜单的 hover 事件
const handleMenuEnter = () => {
  if (props.trigger !== 'hover') return
  isMouseInMenu = true
  if (hideTimer) {
    clearTimeout(hideTimer)
    hideTimer = null
  }
}

const handleMenuLeave = () => {
  if (props.trigger !== 'hover') return
  isMouseInMenu = false
  scheduleHide()
}

// 点击模式
const handleToggleMenu = () => {
  if (visible.value) {
    visible.value = false
  } else {
    updateMenuPosition()
    visible.value = true
  }
}

const handleClose = () => {
  visible.value = false
}

const handleItemClick = (item: DropdownMenuItem) => {
  visible.value = false
  emit('select', item)
}

// 点击模式下的全局点击关闭
const handleGlobalClick = (event: MouseEvent) => {
  if (props.trigger !== 'click' || !visible.value) return
  
  const target = event.target as HTMLElement
  if (!target.closest('.common-dropdown-menu') && !target.closest('.dropdown-menu-container')) {
    visible.value = false
  }
}

onMounted(() => {
  if (props.trigger === 'click') {
    document.addEventListener('click', handleGlobalClick)
  }
})

onUnmounted(() => {
  if (hideTimer) {
    clearTimeout(hideTimer)
  }
  document.removeEventListener('click', handleGlobalClick)
})
</script>

<style lang="scss">
// 注意：这里不使用 scoped，因为菜单通过 Teleport 渲染到 body
.common-dropdown-menu {
  position: relative;
  display: flex;
  width: 100%;
  height: 100%;
}

.common-dropdown-menu .trigger-button {
  cursor: pointer;
  width: 100%;
  height: 100%;
}

.common-dropdown-menu .default-trigger {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  border-radius: 4px;
  transition: all 0.15s ease;

  i {
    font-size: 14px;
  }
}

.dropdown-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 999;
}

.dropdown-menu-container {
  position: fixed;
  background-color: var(--color-bg-primary);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  box-shadow: var(--shadow-sm);
  z-index: 1000;
  animation: dropdown-fade-in-top 0.15s ease-out;
}

// placement-top 的动画
@keyframes dropdown-fade-in-top {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(-100%) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(-100%) scale(1);
  }
}

// placement-bottom 的动画
@keyframes dropdown-fade-in-bottom {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(0) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0) scale(1);
  }
}

// placement-right 的动画
@keyframes dropdown-fade-in-right {
  from {
    opacity: 0;
    transform: translateY(-50%) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(-50%) scale(1);
  }
}

.dropdown-menu-container.placement-bottom {
  animation-name: dropdown-fade-in-bottom;
}

.dropdown-menu-container.placement-right {
  animation-name: dropdown-fade-in-right;
}

.dropdown-menu-container .dropdown-column {
  max-height: 60vh;
  overflow-y: auto;
  
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

.dropdown-menu-container .dropdown-item {
  display: flex;
  align-items: center;
  padding: 0 10px;
  cursor: pointer;
  transition: all 0.15s ease;
  
  &:first-of-type {
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
  }

  &:last-of-type {
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
    
    .item-content {
      border-bottom: none;
    }
  }
  
  &:hover {
    background-color: var(--hover-overlay);
  }
  
  .item-content {
    display: flex;
    align-items: center;
    width: 100%;
    height: 42px;
    padding: 0 8px;
    border-bottom: 1px solid var(--color-border);
  }

  .item-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    color: var(--color-text-primary);
    
    i {
      font-size: 13px;
    }
  }

  .item-text {
    margin-left: 8px;
    white-space: nowrap;
    font-size: 13px;
    color: var(--color-text-primary);
  }
}
</style>
