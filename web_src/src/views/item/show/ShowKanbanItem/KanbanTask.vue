<template>
  <div class="kanban-task" :class="{ 'is-completed': isCompleted }" @click="$emit('click')" @contextmenu.prevent="handleContextMenu">
    <div class="task-tags" v-if="displayTags.length">
      <span
        v-for="(tag, idx) in displayTags.slice(0, 3)"
        :key="idx"
        class="task-tag"
        :style="{ background: tagBgMap[tag.color] || tagBgMap.gray, color: tagColorMap[tag.color] || tagColorMap.gray }"
      >{{ tag.text }}</span>
    </div>
    <div class="task-title">
      <span v-if="taskData?.priority === 'high'" class="priority-icon">🔺</span>
      <span v-else-if="taskData?.priority === 'low'" class="priority-icon">🟢</span>
      {{ taskPage?.page_title || '' }}
    </div>
    <div class="task-meta">
      <span v-if="taskData?.assignee_username" class="meta-assignee">
        <i class="fas fa-user meta-icon"></i>
        {{ taskData.assignee_username }}
      </span>
      <span
        v-if="taskData?.due_date"
        class="meta-due"
        :class="{ 'is-overdue': isOverdue }"
      >
        <i class="fas fa-calendar meta-icon"></i>
        {{ formatDate(taskData.due_date) }}
      </span>
      <span v-if="taskData?.linked_pages?.length" class="meta-linked">
        <i class="fas fa-link meta-icon"></i>
        {{ taskData.linked_pages[0].page_title }}
        <template v-if="taskData.linked_pages.length > 1">+{{ taskData.linked_pages.length - 1 }}</template>
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import ContextmenuModal from '@/components/ContextmenuModal'
import type { KanbanTaskData, KanbanTag, TaskPageInfo } from './types'

interface Props {
  pageId: string
  taskPage?: TaskPageInfo
  taskData?: KanbanTaskData
  isEditable: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  click: []
  copy: []
  delete: []
  complete: []
}>()

const { t } = useI18n()

const tagsFromExtInfo = (): KanbanTag[] => {
  if (props.taskPage?.ext_info) {
    try {
      const info = JSON.parse(props.taskPage.ext_info)
      if (Array.isArray(info.tags) && info.tags.length) return info.tags
    } catch { /* ignore */ }
  }
  return []
}

const displayTags = computed(() => props.taskData?.tags?.length ? props.taskData.tags : tagsFromExtInfo())

const isCompleted = computed(() => {
  if (props.taskData) return !!props.taskData.completed
  if (props.taskPage?.ext_info) {
    try { return !!JSON.parse(props.taskPage.ext_info).completed } catch { return false }
  }
  return false
})

const tagColorMap: Record<string, string> = {
  red: '#dc3545',
  orange: '#fd7e14',
  yellow: '#b8860b',
  green: '#28a745',
  blue: '#007bff',
  purple: '#6f42c1',
  gray: '#6c757d',
}

const tagBgMap: Record<string, string> = {
  red: 'rgba(220, 53, 69, 0.1)',
  orange: 'rgba(253, 126, 20, 0.1)',
  yellow: 'rgba(184, 134, 11, 0.1)',
  green: 'rgba(40, 167, 69, 0.1)',
  blue: 'rgba(0, 123, 255, 0.1)',
  purple: 'rgba(111, 66, 193, 0.1)',
  gray: 'rgba(108, 117, 125, 0.1)',
}

const isOverdue = computed(() => {
  if (!props.taskData?.due_date) return false
  return props.taskData.due_date < new Date().toISOString().slice(0, 10)
})

const formatDate = (dateStr: string) => {
  if (!dateStr) return ''
  const parts = dateStr.split('-')
  return `${parseInt(parts[1])}/${parseInt(parts[2])}`
}

const handleContextMenu = (e: MouseEvent) => {
  ContextmenuModal({
    x: e.clientX,
    y: e.clientY,
    list: [
      {
        icon: ['fas', 'fa-expand'],
        text: t('item.kanban_task_detail'),
        onclick: () => emit('click'),
      },
      {
        icon: ['fas', 'fa-copy'],
        text: t('item.kanban_copy_task'),
        onclick: () => emit('copy'),
      },
      {
        icon: ['fas', 'fa-check-circle'],
        text: t('item.kanban_complete_task'),
        hidden: !props.isEditable,
        onclick: () => emit('complete'),
      },
      {
        icon: ['fas', 'fa-trash'],
        text: t('common.delete'),
        hidden: !props.isEditable,
        onclick: () => emit('delete'),
      },
    ],
  })
}
</script>

<style lang="scss" scoped>
.kanban-task {
  background: var(--color-obvious);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  padding: 10px;
  margin-bottom: 8px;
  cursor: pointer;
  transition: all 0.15s ease;
  position: relative;

  &:hover {
    box-shadow: var(--shadow-default);
    border-color: var(--color-inactive);
  }
}

.task-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-bottom: 6px;
}

.task-tag {
  font-size: 11px;
  padding: 1px 6px;
  border-radius: 3px;
  line-height: 1.4;
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 500;
}

.task-title {
  font-size: var(--font-size-m);
  font-weight: 500;
  color: var(--color-text-primary);
  line-height: 1.4;
  word-break: break-word;
}

.priority-icon {
  margin-right: 2px;
}

.task-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 6px;
  font-size: 11px;
  color: var(--color-text-secondary);
}

.meta-icon {
  font-size: 10px;
}

.meta-assignee,
.meta-due,
.meta-linked {
  display: inline-flex;
  align-items: center;
  gap: 3px;
}

.meta-due.is-overdue {
  color: var(--color-red);
}

.meta-creator {
  color: var(--color-text-secondary);
  font-size: 10px;
}

.kanban-task.is-completed {
  opacity: 0.6;

  .task-title {
    text-decoration: line-through;
    color: var(--color-text-secondary);
  }
}
</style>
