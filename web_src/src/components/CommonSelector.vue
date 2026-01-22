<template>
  <div class="common-type-selector">
    <!-- Contextmenu 模式（简单场景） -->
    <div
      v-if="!useAntSelect"
      class="contextmenu-mode text-default clickable center"
      @click="selectHandle"
    >
      {{ displayLabel }} &nbsp;&nbsp;<i class="fas fa-angle-down"></i>
    </div>

    <!-- a-select 模式（复杂场景：多选/搜索/选项多） -->
    <a-select
      v-else
      v-model:value="internalValue"
      :placeholder="placeholder || selectorPlaceholder"
      :show-search="showSearch"
      :mode="multiple ? 'multiple' : undefined"
      :options="normalizedOptions"
      :filter-option="filterOption"
      class="ant-select-mode"
      @change="handleAntSelectChange"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import ContextmenuModal from './ContextmenuModal'

// Props 类型定义
interface Option {
  label: string
  value: string | number | boolean
  [key: string]: any
}

interface Props {
  // 向后兼容的 props
  selectorLabel?: string
  selectorValue?: string | number | boolean
  options: Array<{ label: string; value: string | number | boolean }>
  placeholder?: string

  // 新增 props
  value?: string | number | boolean | (string | number | boolean)[]
  showSearch?: boolean
  multiple?: boolean
  searchThreshold?: number
}

const props = withDefaults(defineProps<Props>(), {
  showSearch: false,
  multiple: false,
  searchThreshold: 10,
})

// Emits 定义
const emits = defineEmits<{
  'update:selectorLabel': [value: string]
  'update:selectorValue': [value: string | number]
  'update:value': [value: any]
  'change': [value: any, option: any]
}>()

// 判断是否使用 a-select 模式
const useAntSelect = computed(() => {
  return (
    props.multiple ||
    props.showSearch ||
    (props.options?.length || 0) > props.searchThreshold
  )
})

// 格式化选项，确保 value 为字符串类型（用于 a-select）
const normalizedOptions = computed(() => {
  return props.options.map((opt) => ({
    label: opt.label,
    value: String(opt.value),
    originalValue: opt.value, // 保留原始值用于比较
  }))
})

// 显示的文本（Contextmenu 模式）
const displayLabel = computed(() => {
  if (!useAntSelect.value) {
    // 优先使用 selectorLabel
    if (props.selectorLabel) {
      return props.selectorLabel
    }

    // 如果没有 selectorLabel，根据当前值查找对应的 label
    const currentValue = props.value !== undefined ? props.value : props.selectorValue
    if (currentValue !== undefined) {
      const option = findOptionByValue(currentValue)
      if (option) {
        return option.label
      }
    }

    // 默认显示占位符
    return props.placeholder || '点此选择'
  }
  return ''
})

// a-select 模式的占位符
const selectorPlaceholder = computed(() => {
  return props.multiple ? '请选择' : '请选择'
})

// 内部值处理
const internalValue = computed({
  get: () => {
    if (!useAntSelect.value) {
      // Contextmenu 模式不使用 internalValue
      return undefined
    }

    // a-select 模式
    if (props.value !== undefined) {
      // 处理多选和单选
      if (props.multiple && Array.isArray(props.value)) {
        // 多选：转换为字符串数组
        return props.value.map((v) => normalizeValue(v))
      } else {
        // 单选：转换为字符串
        return normalizeValue(props.value)
      }
    }

    // 向后兼容：使用 selectorValue
    if (props.selectorValue !== undefined) {
      return normalizeValue(props.selectorValue)
    }

    return undefined
  },
  set: (val: any) => {
    if (!useAntSelect.value) {
      return
    }

    // 将 a-select 的字符串值转换回原始类型
    const normalizedVal = denormalizeValue(val)
    emits('update:value', normalizedVal)

    // 如果是单选，同时更新 selectorLabel（向后兼容）
    if (!props.multiple) {
      const option = findOptionByValue(normalizedVal)
      if (option) {
        emits('update:selectorLabel', option.label)
        emits('update:selectorValue', option.value)
      }
    }
  },
})

// 标准化值为字符串（用于比较）
function normalizeValue(val: string | number | boolean): string {
  return String(val)
}

// 反标准化值（从字符串转为原始类型）
function denormalizeValue(val: string | string[]): string | number | boolean | (string | number | boolean)[] {
  if (Array.isArray(val)) {
    return val.map((v) => denormalizeSingleValue(v)) as (string | number | boolean)[]
  }
  return denormalizeSingleValue(val)
}

// 反标准化单个值
function denormalizeSingleValue(val: string): string | number | boolean {
  // 先尝试在 options 中找到对应的原始值
  const option = props.options.find((opt) => normalizeValue(opt.value) === val)
  if (option) {
    return option.value
  }

  // 特殊处理布尔值的字符串形式
  if (val === 'true') return true
  if (val === 'false') return false

  // 如果找不到，尝试转换回数字（如果原值看起来像数字）
  const numVal = Number(val)
  if (!isNaN(numVal) && val !== '') {
    return numVal
  }

  // 默认返回字符串
  return val
}

// 根据值查找选项（兼容数字和字符串）
function findOptionByValue(val: string | number): Option | undefined {
  const normalizedVal = normalizeValue(val)
  return props.options.find((opt) => normalizeValue(opt.value) === normalizedVal)
}

// a-select 搜索过滤
function filterOption(input: string, option: any) {
  return option.label.toLowerCase().includes(input.toLowerCase())
}

// Contextmenu 模式的选择处理
function selectHandle(e: MouseEvent) {
  ContextmenuModal({
    x: e.x,
    y: e.y,
    list: props.options.map((v) => ({
      text: v.label,
      onclick: () => {
        emits('update:selectorLabel', v.label)
        emits('update:selectorValue', v.value)
        emits('update:value', v.value)
        emits('change', v.value, v)
      },
    })),
  })
}

// a-select 模式的变化处理
function handleAntSelectChange(value: any, option: any) {
  const normalizedVal = denormalizeValue(value)
  emits('change', normalizedVal, option)
}
</script>

<style scoped lang="scss">
.common-type-selector {
  width: 100%;
  height: 100%;
}

// Contextmenu 模式样式
.contextmenu-mode {
  cursor: pointer;
  user-select: none;
  transition: all 0.15s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  padding: 0 12px;

  &:hover {
    opacity: 0.8;
  }

  i {
    margin-left: 4px;
  }
}

// a-select 模式样式
.ant-select-mode {
  width: 100%;
  height: 100%;

  :deep(.ant-select-selector) {
    background: var(--color-bg-primary);
    border-color: var(--color-border);
    color: var(--color-text-primary);
    box-shadow: none;
    transition: all 0.15s ease;
  }

  :deep(.ant-select-selector:hover) {
    border-color: var(--color-border);
  }

  :deep(.ant-select-focused .ant-select-selector) {
    border-color: var(--color-primary);
    box-shadow: 0 0 0 2px rgba(52, 58, 64, 0.1);
  }

  // 下拉框样式
  :deep(.ant-select-dropdown) {
    background: var(--color-bg-primary);
    border-color: var(--color-border);

    .ant-select-item {
      color: var(--color-text-primary);

      &:hover {
        background: var(--color-bg-secondary);
      }

      &.ant-select-item-option-selected {
        background: var(--color-bg-secondary);
        color: var(--color-primary);
        font-weight: 500;
      }
    }
  }

  // 多选模式标签样式
  :deep(.ant-select-selection-item) {
    background: var(--color-bg-secondary);
    border-color: var(--color-border);
    color: var(--color-text-primary);
  }

  // 清除按钮
  :deep(.ant-select-clear) {
    color: var(--color-text-secondary);

    &:hover {
      color: var(--color-text-primary);
    }
  }
}

// 暗黑主题适配
[data-theme='dark'] {
  .ant-select-mode {
    :deep(.ant-select-focused .ant-select-selector) {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 2px rgba(74, 158, 255, 0.2);
    }

    :deep(.ant-select-arrow) {
      color: var(--color-text-secondary);
    }
  }
}
</style>
