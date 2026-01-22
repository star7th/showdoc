<template>
  <input 
    class="common-input" 
    type="text"
    :value="props.modelValue" 
    v-bind="$attrs" 
    @input="inputHandle"
  />
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue: string
}>()

const emit = defineEmits(['update:modelValue'])

function inputHandle(e: Event) {
  const target = e.target as HTMLInputElement
  emit('update:modelValue', target.value)
}
</script>

<style scoped lang="scss">
.common-input {
  width: 100%;
  height: 40px;
  padding: 0 12px;
  border: 1px solid var(--color-inactive);
  border-radius: 4px;
  background-color: var(--color-obvious) !important; // 确保背景色不被全局样式覆盖
  color: var(--color-primary);
  font-size: var(--font-size-m);
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;

  &:focus {
    border-color: var(--color-active);
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
    background-color: var(--color-obvious) !important; // 确保 focus 时背景色不变
  }

  &::placeholder {
    color: var(--color-grey);
  }

  /* 支持 password 类型 */
  &[type="password"] {
    letter-spacing: 1px;
  }
}
</style>
