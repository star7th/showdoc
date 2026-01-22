<template>
  <div class="table-draggable" ref="wrapper">
    <div :key="tableKey">
      <slot></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, nextTick, onBeforeUnmount } from 'vue'
import Sortable from 'sortablejs'

const props = defineProps<{
  handle?: string
  animate?: number
}>()

const emit = defineEmits<{
  drag: []
  drop: [{ targetObject: any, list: any[] }]
}>()

const wrapper = ref<HTMLElement | null>(null)
const tableKey = ref(0)
let sortableInstance: Sortable | null = null

const makeTableSortable = () => {
  if (!wrapper.value) return

  const tbody = wrapper.value.querySelector('.ant-table-tbody')
  if (!tbody) return

  sortableInstance = Sortable.create(tbody as any, {
    handle: props.handle,
    animation: props.animate || 100,
    onStart: () => {
      emit('drag')
    },
    onEnd: (evt: any) => {
      const { newIndex, oldIndex } = evt
      const list = getCurrentList()
      if (!list) return

      const targetRow = list.splice(oldIndex, 1)[0]
      list.splice(newIndex, 0, targetRow)
      emit('drop', { targetObject: targetRow, list })
    }
  })
}

const getCurrentList = () => {
  if (!wrapper.value) return null
  const tableElement = wrapper.value.querySelector('.ant-table')
  if (!tableElement) return null
  return (tableElement as any).__vnode?.ctx?.data || null
}

onMounted(() => {
  nextTick(() => {
    makeTableSortable()
  })
})

watch(() => props.handle, () => {
  nextTick(() => {
    if (sortableInstance) {
      sortableInstance.destroy()
    }
    makeTableSortable()
  })
})

watch(() => props.animate, () => {
  nextTick(() => {
    if (sortableInstance) {
      sortableInstance.destroy()
    }
    makeTableSortable()
  })
})

onBeforeUnmount(() => {
  if (sortableInstance) {
    sortableInstance.destroy()
  }
})
</script>

<style scoped lang="scss">
.table-draggable {
  :deep(.ant-table-tbody tr) {
    cursor: move;
    transition: background-color 0.15s ease;
  }
}

  :deep(.ant-table-tbody tr.dragging) {
    opacity: 0.5;
    background-color: var(--color-bg-secondary);
  }
}
</style>

