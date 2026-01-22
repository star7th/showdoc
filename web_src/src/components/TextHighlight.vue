<template>
  <span v-html="highlightedText"></span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  queries: string[]
}>()

const slots = defineSlots<{
  default: () => any
}>()

const text = computed(() => {
  const slotContent = slots.default ? slots.default() : ''
  if (Array.isArray(slotContent) && slotContent.length > 0) {
    return slotContent[0].children || ''
  }
  return ''
})

const highlightedText = computed(() => {
  if (!props.queries || props.queries.length === 0) {
    return text.value
  }

  let result = text.value
  props.queries.forEach(query => {
    if (!query) return
    const regex = new RegExp(`(${query})`, 'gi')
    result = result.replace(regex, '<mark>$1</mark>')
  })
  return result
})
</script>

<style scoped>
:deep(mark) {
  background-color: rgba(255, 193, 7, 0.45) !important;
  color: #000 !important;
  padding: 2px 5px !important;
  border-radius: 3px !important;
  font-weight: 600 !important;
  box-shadow: 0 0 0 1px rgba(255, 193, 7, 0.7) !important;
  text-shadow: 0 0 1px rgba(0, 0, 0, 0.1) !important;
}

/* 暗黑主题适配 */
[data-theme='dark'] :deep(mark) {
  background-color: rgba(255, 193, 7, 0.25) !important;
  color: #fff !important;
  box-shadow: 0 0 0 1px rgba(255, 193, 7, 0.6) !important;
}
</style>


