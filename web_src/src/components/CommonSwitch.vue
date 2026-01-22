<template>
  <div class="common-switch-wrapper" v-if="label">
    <div
      class="common-switch text-secondary clickable"
      :class="{ checked: is_checked, disabled: disabled }"
      @click="handleClick"
    >
      <template v-if="is_checked">
        <i class="fas fa-toggle-on"></i>
      </template>
      <template v-else>
        <i class="fas fa-toggle-off"></i>
      </template>
    </div>
    <span class="switch-label">{{ label }}</span>
  </div>
  <div
    v-else
    class="common-switch text-secondary clickable"
    :class="{ checked: is_checked, disabled: disabled }"
    @click="handleClick"
  >
    <template v-if="is_checked">
      <i class="fas fa-toggle-on"></i>
    </template>
    <template v-else>
      <i class="fas fa-toggle-off"></i>
    </template>
  </div>
</template>
<script setup lang="ts">
import { computed } from 'vue'

// 兼容 string、number 和 boolean 类型
const props = defineProps<{
  modelValue: string | number | boolean
  label?: string
  disabled?: boolean
}>();

const emits = defineEmits<{
  (e: "update:modelValue", value: string | number | boolean): void
  (e: "change", value: string | number | boolean): void
}>();

// 使用弱相等判断，兼容 string、number 和 boolean 类型
const is_checked = computed(() => {
  if (typeof props.modelValue === 'boolean') {
    return props.modelValue
  }
  return props.modelValue == 1
})

function handleClick() {
  if (props.disabled) return

  // 根据输入类型返回对应类型的值，保持类型一致
  let newValue: string | number | boolean

  if (typeof props.modelValue === 'boolean') {
    // 布尔值模式：返回布尔值
    newValue = !is_checked.value
    emits('update:modelValue', newValue)
  } else if (typeof props.modelValue === 'number') {
    // 数字模式：返回 0 或 1
    newValue = is_checked.value ? 0 : 1
    emits('update:modelValue', newValue)
  } else {
    // 字符串模式（默认）：返回 '0' 或 '1'，与旧版保持一致
    newValue = is_checked.value ? '0' : '1'
    emits('update:modelValue', newValue)
  }

  // 触发 change 事件，传递新值（兼容 a-checkbox 的行为）
  emits('change', newValue)
}
</script>
<style scoped lang="scss">
.common-switch-wrapper {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.common-switch {
  font-size: 20px;
  display: inline;
  vertical-align: middle;
  cursor: pointer;
  transition: opacity 0.15s ease;

  &.disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  &.checked {
    color: var(--color-active);
  }
}

.switch-label {
  font-size: 14px;
  color: var(--color-text-primary);
  user-select: none;
  cursor: pointer;
  vertical-align: middle;
}
</style>
