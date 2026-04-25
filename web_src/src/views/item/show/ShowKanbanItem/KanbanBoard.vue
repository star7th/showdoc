<template>
  <div class="kanban-board" ref="boardRef" :class="{ 'with-filter-bar': showFilterBar }">
    <div class="kanban-lists">
      <div v-if="isEditable" class="add-list-btn" @click="handleAddList">
        <i class="fas fa-plus"></i>
        {{ $t('item.kanban_add_list') }}
      </div>
      <KanbanList
        v-for="list in boardData.lists"
        :key="list.id"
        :list="list"
        :task-ids="deduplicatedTaskIds(list.id)"
        :task-pages="taskPages"
        :task-details-cache="taskDetailsCache"
        :is-editable="isEditable"
        :search-keyword="searchKeyword"
        :filters="filters"
        :is-last-list="boardData.lists.length === 1"
        :highlighted-task-ids="highlightedTaskIds"
        @save-board="(d) => emit('saveBoard', d)"
        @create-task="(listId, title) => emit('createTask', listId, title)"
        @open-task-detail="(pageId) => emit('openTaskDetail', pageId)"
        @delete-task="(pageId) => emit('deleteTask', pageId)"
        @complete-task="(pageId) => emit('completeTask', pageId)"
        @copy-task="(pageId) => emit('copyTask', pageId)"
        @add-list="(title, pos) => emit('addList', title, pos, list.id)"
        @update-list="(listId, title) => emit('updateList', listId, title)"
        @delete-list="(listId) => emit('deleteList', listId)"
        @archive-list="(listId) => emit('archiveList', listId)"
        @move-task="(pageId, fromListId, toListId, toIdx) => emit('moveTask', pageId, fromListId, toListId, toIdx)"
        @batch-move="(pageIds) => emit('batchMove', pageIds)"
        @batch-complete="(pageIds) => emit('batchComplete', pageIds)"
        @batch-delete="(pageIds) => emit('batchDelete', pageIds)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Sortable from 'sortablejs'
import KanbanList from './KanbanList.vue'
import PromptModal from '@/components/PromptModal'
import type { KanbanBoardData, KanbanTaskData, TaskPageInfo } from './types'

interface Props {
  boardData: KanbanBoardData
  taskPages: TaskPageInfo[]
  taskDetailsCache: Record<string, KanbanTaskData>
  isEditable: boolean
  searchKeyword: string
  filters: any
  highlightedTaskIds: Set<string>
  showFilterBar: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  saveBoard: [data: KanbanBoardData]
  moveTask: [pageId: string, fromListId: string, toListId: string, toIndex: number]
  createTask: [listId: string, title: string]
  openTaskDetail: [pageId: string]
  copyTask: [pageId: string]
  deleteTask: [pageId: string]
  completeTask: [pageId: string]
  addList: [title: string, position: 'left' | 'right', refListId?: string]
  updateList: [listId: string, title: string]
  deleteList: [listId: string]
  archiveList: [listId: string]
  batchMove: [pageIds: string[]]
  batchComplete: [pageIds: string[]]
  batchDelete: [pageIds: string[]]
}>()

const { t } = useI18n()

const handleAddList = async () => {
  const title = await PromptModal(t('item.kanban_add_list'), '', t('item.kanban_list_name'))
  if (title) {
    emit('addList', title, 'right')
  }
}

const boardRef = ref<HTMLElement | null>(null)
let listSortable: Sortable | null = null

const initListSortable = () => {
  if (!boardRef.value || !props.isEditable) return
  const container = boardRef.value.querySelector('.kanban-lists')
  if (!container) return
  listSortable = new Sortable(container, {
    animation: 150,
    handle: '.list-header',
    ghostClass: 'list-ghost',
    draggable: '.kanban-list',
    onEnd: (evt) => {
      if (evt.oldIndex === evt.newIndex) return
      const offset = container.querySelector('.add-list-btn') ? 1 : 0
      const oldIdx = evt.oldIndex! - offset
      const newIdx = evt.newIndex! - offset
      if (oldIdx < 0 || newIdx < 0) return
      const lists = [...props.boardData.lists]
      const [moved] = lists.splice(oldIdx, 1)
      lists.splice(newIdx, 0, moved)
      lists.forEach((l, i) => l.position = i + 1)
      const newData = { ...props.boardData, lists: [...lists] }
      emit('saveBoard', newData)
    },
  })
}

onMounted(() => {
  nextTick(() => initListSortable())
})

onBeforeUnmount(() => {
  listSortable?.destroy()
})

watch(() => props.boardData.lists.length, () => {
  nextTick(() => {
    listSortable?.destroy()
    listSortable = null
    initListSortable()
  })
})

const deduplicatedTaskIds = (listId: string): string[] => {
  const ids = props.boardData.tasks_order[listId]
  if (!ids || !ids.length) return []
  return [...new Set(ids)]
}

const getBoardData = () => props.boardData

defineExpose({ getBoardData })
</script>

<style lang="scss" scoped>
.kanban-board {
  margin-top: 130px;
  padding: 16px 24px;
  overflow-x: auto;
  min-height: calc(100vh - 130px);

  &.with-filter-bar {
    margin-top: 188px;
    min-height: calc(100vh - 188px);
  }
}

.kanban-lists {
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.list-ghost {
  opacity: 0.4;
  background: var(--color-bg-secondary);
  border-radius: 8px;
}

.add-list-btn {
  min-width: 280px;
  max-width: 320px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  background: var(--color-bg-secondary);
  border: 1px dashed var(--color-border);
  border-radius: 8px;
  color: var(--color-text-secondary);
  font-size: var(--font-size-m);
  cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0;

  &:hover {
    border-color: var(--color-active);
    color: var(--color-active);
    background: var(--hover-overlay);
  }
}
</style>
