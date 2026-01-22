<template>
  <div class="common-modal" :class="{ show }" @wheel.stop>
    <div class="modal-content" @click.stop :style="modalStyle">
      <ModalHeader :icon="props.icon" :title="props.title" :useClose="props.useClose" :useLine="props.useLine"
        @close="emits('close')">
        <template #left>
          <slot name="left"></slot>
        </template>
        <template #right>
          <!-- 头部按钮组 -->
          <div v-if="headerButtons && headerButtons.length > 0" class="header-buttons">
            <a-button
              v-for="(btn, index) in headerButtons"
              :key="index"
              :type="btn.type || 'default'"
              :size="btn.size || 'small'"
              :danger="btn.danger"
              @click.stop="btn.onClick"
            >
              <i v-if="btn.icon" :class="getIconClass(btn.icon)"></i>
              <span v-if="btn.text" :class="{ 'ml-1': btn.icon }">{{ btn.text }}</span>
            </a-button>
          </div>
          <!-- 默认插槽 -->
          <slot name="right"></slot>
        </template>
      </ModalHeader>
      <div class="modal-body">
        <slot></slot>
      </div>
      <!-- 底部插槽 -->
      <div v-if="$slots.footer" class="modal-footer">
        <slot name="footer"></slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import ModalHeader from './ModalHeader.vue';

interface HeaderButton {
  text?: string
  icon?: string | string[]
  type?: 'default' | 'primary'
  size?: 'small' | 'middle' | 'large'
  danger?: boolean
  onClick: () => void
}

const props = withDefaults(defineProps<{
  show?: boolean
  icon?: string[]
  title?: string
  useClose?: boolean
  useLine?: boolean
  width?: string
  maxWidth?: string
  minWidth?: string
  height?: string
  headerButtons?: HeaderButton[]
}>(), {
  show: false,
  useClose: true,
  useLine: true // 默认显示分隔线
})

const emits = defineEmits(['close'])

// 计算样式
const modalStyle = computed(() => {
  const style: Record<string, string> = {}
  
  // 如果传了 width，直接使用固定宽度
  if (props.width) {
    style['width'] = props.width
  } else {
    // 否则使用 maxWidth 和 minWidth
    if (props.maxWidth) {
      style['--max-width'] = props.maxWidth
    }
    if (props.minWidth) {
      style['--min-width'] = props.minWidth
    }
  }

  // 如果传了 height，设置固定高度
  if (props.height) {
    style['height'] = props.height
  }
  
  return style
})

// 处理图标数组，转换为 FontAwesome 类名格式
function getIconClass(icon: string[] | string): string {
  if (Array.isArray(icon)) {
    const style = icon[0]; // fas, far 等
    const iconName = icon[1]; // 图标名称

    // 如果图标名称已经包含 fa- 前缀，就直接使用
    if (iconName.startsWith('fa-')) {
      return `${style} ${iconName}`
    }

    // 否则，添加 fa- 前缀
    return `${style} fa-${iconName}`
}
  return icon;
}
</script>

<style lang="scss" scoped>
// 弹窗容器（克制设计）
.common-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 150ms ease-in-out; // 由 200ms 优化为 150ms
  z-index: 1000;
  background-color: var(--color-inactive);
  user-select: none;
  pointer-events: none;

  &.show {
    opacity: 1;
    pointer-events: auto;

    .modal-content {
      transform: scale(1);
    }
  }
}

// 弹窗内容（克制设计）
.modal-content {
  max-width: var(--max-width);
  min-width: var(--min-width);
  background-color: var(--color-default);
  border-radius: 8px;
  box-shadow: var(--shadow-lg); // 使用更清晰的阴影层级
  transform: scale(0.95); // 由 0.3 优化为 0.95（更克制的缩放）
  transition: transform 150ms ease-in-out; // 由 200ms 优化为 150ms
  user-select: text;

  // 当设置了固定高度时，使用 flex 布局
  &[style*="height"] {
    display: flex;
    flex-direction: column;
  }
}

.modal-body {
  // 默认没有固定高度时不需要特殊样式
  // 当设置了固定高度时，内容区可以滚动
  .modal-content[style*="height"] & {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
  }
}

// 弹窗底部（克制设计）
.modal-footer {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 80px; // 由 90px 优化为 80px
  padding: 0 24px; // 由 20px 优化为 24px，与 header 一致
}

// Header 按钮组样式（不定义 .modal-header，避免与 ModalHeader 组件冲突）
:deep(.header-buttons) {
  display: flex;
  align-items: center;
  gap: 10px; // 由 8px 优化为 10px
  margin-right: 16px;

  .ant-btn {
    display: flex;
    align-items: center;
    font-size: 13px;
    border-radius: 6px; // 由 4px 优化为 6px
    height: 28px;
    padding: 0 12px;
    transition: all 0.15s ease; // 添加过渡效果

    &.ant-btn-sm {
      height: 24px;
      padding: 0 10px;
      font-size: 12px;
    }

    .ml-1 {
      margin-left: 4px;
    }
  }
}
</style>
