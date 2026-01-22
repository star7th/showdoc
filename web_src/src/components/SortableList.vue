<template>
  <div class="sortable-list" ref="wrapper">
    <div :key="listKey" :class="listClass">
      <slot></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, nextTick, onBeforeUnmount } from 'vue'
import Sortable from 'sortablejs'

const props = withDefaults(defineProps<{
  handle?: string
  animate?: number
  listClass?: string
  tag?: string
}>(), {
  tag: 'div',
  animate: 100
})

const emit = defineEmits<{
  drag: []
  drop: [{ oldIndex: number, newIndex: number }]
}>()

const wrapper = ref<HTMLElement | null>(null)
const listKey = ref(0)
let sortableInstance: Sortable | null = null

const makeSortable = () => {
  if (!wrapper.value) return

  const listElement = props.tag ? wrapper.value.querySelector(props.tag) : wrapper.value?.firstElementChild
  if (!listElement) return

  sortableInstance = Sortable.create(listElement as any, {
    handle: props.handle,
    animation: props.animate,
    ghostClass: 'sortable-ghost',
    dragClass: 'sortable-drag',
    chosenClass: 'sortable-chosen',
    onStart: () => {
      emit('drag')
    },
    onEnd: (evt: any) => {
      const { newIndex, oldIndex } = evt
      emit('drop', { oldIndex, newIndex })
    }
  })
}

onMounted(() => {
  nextTick(() => {
    makeSortable()
  })
})

watch(() => props.handle, () => {
  nextTick(() => {
    if (sortableInstance) {
      sortableInstance.destroy()
    }
    makeSortable()
  })
})

watch(() => props.animate, () => {
  nextTick(() => {
    if (sortableInstance) {
      sortableInstance.destroy()
    }
    makeSortable()
  })
})

watch(() => props.tag, () => {
  nextTick(() => {
    if (sortableInstance) {
      sortableInstance.destroy()
    }
    makeSortable()
  })
})

onBeforeUnmount(() => {
  if (sortableInstance) {
    sortableInstance.destroy()
  }
})
</script>

<style scoped lang="scss">
.sortable-list {
  :deep(.sortable-ghost) {
    opacity: 0.4;
    background-color: var(--color-bg-secondary);
  }

  :deep(.sortable-drag) {
    opacity: 1;
    background-color: var(--color-bg-secondary);
    cursor: move;
  }

  :deep(.sortable-chosen) {
    opacity: 1;
  }
}

.sortable-list {
  :deep(.sortable-ghost .group-item) {
    background-color: var(--color-bg-secondary);
    border: 1px dashed var(--color-border);
  }
}

.sortable-list {
  :deep([class*="group-item"]) {
    cursor: move;
    transition: background-color 0.15s ease, transform 0.15s ease;
  }
}
</style>

