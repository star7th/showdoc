<template>
  <div class="common-tab" :class="[`type-${type}`, { disabled }]">
    <!-- Tab 头部 -->
    <div class="tab-header">
      <div class="tab-container">
        <div
          v-for="(item, index) in items"
          :key="item.value"
          class="tab-item"
          :class="{ active: currentValue === item.value, disabled: item.disabled }"
          @click="handleClick(item)"
        >
          <div class="tab-content">
            <div v-if="item.icon" class="tab-icon">
              <i :class="getIconClass(item.icon)"></i>
            </div>
            <div class="tab-text">{{ item.text }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab 内容区域 -->
    <div class="tab-content-wrapper">
      <slot></slot>
      <!-- 动态插槽：根据 value 获取对应的内容 -->
      <template v-for="item in items" :key="item.value">
        <slot v-if="currentValue === item.value" :name="String(item.value)"></slot>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, useSlots, defineEmits, defineProps, defineExpose } from 'vue'

export interface TabItem {
  text: string
  value: string | number
  icon?: string | string[]
  disabled?: boolean
}

const props = withDefaults(
  defineProps<{
    items: TabItem[]
    value?: string | number
    type?: 'segmented' | 'line' | 'card'
    disabled?: boolean
  }>(),
  {
    type: 'segmented',
    disabled: false
  }
)

const emits = defineEmits<{
  updateValue: [value: string | number]
  change: [item: TabItem]
}>()

// 内部维护的值
const currentValue = computed({
  get: () => props.value,
  set: (val) => emits('updateValue', val)
})

// 获取插槽实例
const slots = useSlots()

// 检查是否有动态插槽（用于 tab 内容切换）
const hasDynamicSlot = computed(() => {
  if (!slots.default) return false
  return Object.keys(slots).some(key => key !== 'default')
})

// 暴露方法供外部调用
const setActive = (value: string | number) => {
  emits('updateValue', value)
}

defineExpose({
  setActive
})

const handleClick = (item: TabItem) => {
  if (props.disabled || item.disabled) return
  emits('updateValue', item.value)
  emits('change', item)
}

// 处理图标数组，转换为 FontAwesome 类名格式
function getIconClass(icon: string[] | string): string {
  if (Array.isArray(icon)) {
    const style = icon[0] // fas, far 等
    const iconName = icon[1] // 图标名称

    // 如果图标名称已经包含 fa- 前缀，就直接使用
    if (iconName.startsWith('fa-')) {
      return `${style} ${iconName}`
    }

    // 否则，添加 fa- 前缀
    return `${style} fa-${iconName}`
  }
  return icon
}
</script>

<style lang="scss" scoped>
.common-tab {
  width: 100%;

  &.disabled {
    opacity: 0.5;
    pointer-events: none;
  }
}

.tab-header {
  width: 100%;

  .tab-container {
    display: flex;
    align-items: center;
    width: 100%;
  }
}

.tab-content-wrapper {
  margin-top: 16px;
  width: 100%;

  > * {
    width: 100%;
  }
}

.tab-item {
  position: relative;
  cursor: pointer;
  transition: all 0.15s ease;
  user-select: none;

  &.disabled {
    opacity: 0.4;
    cursor: not-allowed;

    &:hover {
      background-color: transparent !important;
      color: var(--color-text-secondary) !important;
    }
  }

  .tab-content {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 20px;
    white-space: nowrap;
    min-height: 36px;
  }

  .tab-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 8px;
    color: var(--color-text-primary);
    transition: color 0.15s ease;

    i {
      font-size: 14px;
    }
  }

  .tab-text {
    font-size: 14px;
    font-weight: 500;
    color: var(--color-text-primary);
    transition: color 0.15s ease;
  }

  // hover 和 active 状态需要更高的优先级来覆盖内部元素的颜色
  &:hover:not(.disabled),
  &.active {
    .tab-icon,
    .tab-text {
      color: var(--color-active);
    }
  }
}

// 分段样式（类似按钮组）
.common-tab.type-segmented {
  .tab-header .tab-container {
    background-color: var(--color-bg-secondary);
    padding: 3px;
    border-radius: 8px;
    gap: 2px;
  }

  .tab-item {
    flex: 1;
    border-radius: 6px;
    color: var(--color-text-primary);
    transition: all 0.15s ease;

    .tab-content {
      padding: 8px 16px;
    }

    &:hover:not(.disabled) {
      background-color: rgba(0, 0, 0, 0.04);
    }

    &.active {
      background-color: rgba(0, 0, 0, 0.08);
      color: var(--color-primary);

      .tab-icon,
      .tab-text {
        color: var(--color-primary);
        font-weight: 500;
      }
    }

    &:hover:not(.disabled) {
      .tab-icon,
      .tab-text {
        color: var(--color-primary);
      }
    }
  }
}

// 暗黑主题适配 - 分段样式
[data-theme='dark'] .common-tab.type-segmented {
  .tab-item.active {
    background-color: rgba(255, 255, 255, 0.15);
    color: var(--color-primary);
  }
}

// 线条样式
.common-tab.type-line {
  .tab-header .tab-container {
    border-bottom: 2px solid var(--color-border);
    gap: 0;
  }

  .tab-item {
    margin-bottom: -2px;
    border-bottom: 2px solid transparent;
    transition: all 0.15s ease;

    .tab-content {
      padding: 12px 24px;
    }

    &:hover:not(.disabled) {
      color: var(--color-active);
    }

    &.active {
      color: var(--color-active);
      border-bottom-color: var(--color-active);
      border-bottom-width: 3px;
      font-weight: 600;

      .tab-icon,
      .tab-text {
        color: var(--color-active);
        font-weight: 600;
      }
    }

    &:hover:not(.disabled) {
      .tab-icon,
      .tab-text {
        color: var(--color-active);
      }
    }
  }
}

// 卡片样式
.common-tab.type-card {
  .tab-header .tab-container {
    gap: -1px;
  }

  .tab-item {
    flex: 1;
    background-color: var(--color-bg-secondary);
    border: 1px solid var(--color-border);
    border-radius: 8px;
    transition: all 0.15s ease;

    &:first-of-type {
      border-top-right-radius: 0;
      border-bottom-right-radius: 0;
    }

    &:last-of-type {
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
    }

    &:not(:first-of-type):not(:last-of-type) {
      border-radius: 0;
      margin-left: -1px;
    }

    .tab-content {
      padding: 10px 20px;
    }

    &:hover:not(.disabled) {
      border-color: var(--color-active);
      color: var(--color-active);
      background-color: var(--hover-overlay);

      .tab-icon,
      .tab-text {
        color: var(--color-active);
      }
    }

    &.active {
      background-color: var(--color-active);
      border-color: var(--color-active);
      color: #fff;
      box-shadow: var(--shadow-sm);
      z-index: 1;
      font-weight: 600;

      .tab-icon,
      .tab-text {
        color: #fff;
        font-weight: 600;
      }
    }
  }
}

// 暗黑主题适配
[data-theme='dark'] {
  .common-tab.type-segmented .tab-container {
    background-color: var(--color-bg-secondary);
  }

  .common-tab.type-segmented .tab-item.active {
    background-color: #2c2c2e;
  }

  .common-tab.type-card .tab-item {
    background-color: var(--color-bg-secondary);
    border-color: var(--color-border);

    &:hover:not(.disabled) {
      border-color: var(--color-active);
      color: var(--color-active);
      background-color: var(--hover-overlay);
    }

    &.active {
      background-color: var(--color-active);
      border-color: var(--color-active);
    }
  }
}
</style>

