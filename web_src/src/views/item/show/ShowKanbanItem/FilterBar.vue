<template>
  <div class="filter-bar">
    <a-select
      v-model:value="filters.assignee_uid"
      :placeholder="$t('item.kanban_filter_assignee')"
      allow-clear
      style="width: 140px"
      @change="emitFilter"
    >
      <a-select-option value="">{{ $t('item.kanban_filter_no_assignee') }}</a-select-option>
      <a-select-option v-for="m in members" :key="m.uid" :value="String(m.uid)">
        {{ m.username }}
      </a-select-option>
    </a-select>

    <a-select
      v-model:value="filters.creator_uid"
      :placeholder="$t('item.kanban_filter_creator')"
      allow-clear
      style="width: 140px"
      @change="emitFilter"
    >
      <a-select-option v-for="m in members" :key="m.uid" :value="String(m.uid)">
        {{ m.username }}
      </a-select-option>
    </a-select>

    <a-select
      v-model:value="filters.priority"
      :placeholder="$t('item.kanban_filter_priority')"
      allow-clear
      style="width: 120px"
      @change="emitFilter"
    >
      <a-select-option value="high">🔺 {{ $t('item.kanban_priority_high') }}</a-select-option>
      <a-select-option value="medium">{{ $t('item.kanban_priority_medium') }}</a-select-option>
      <a-select-option value="low">🟢 {{ $t('item.kanban_priority_low') }}</a-select-option>
    </a-select>

    <a-select
      v-model:value="filters.tag"
      :placeholder="$t('item.kanban_filter_tag')"
      allow-clear
      style="width: 120px"
      @change="emitFilter"
    >
      <a-select-option v-for="tag in allTags" :key="tag" :value="tag">{{ tag }}</a-select-option>
    </a-select>

    <a-range-picker
      v-model:value="dueDateRange"
      :placeholder="[$t('item.kanban_filter_due_date_start'), $t('item.kanban_filter_due_date_end')]"
      style="width: 220px"
      value-format="YYYY-MM-DD"
      @change="onDueDateRangeChange"
    />

    <a-checkbox v-model:checked="filters.no_due_date" @change="emitFilter">
      {{ $t('item.kanban_filter_no_due_date') }}
    </a-checkbox>

    <a-checkbox v-model:checked="filters.show_completed" @change="emitFilter">
      {{ $t('item.kanban_show_completed') }}
    </a-checkbox>

    <a-button size="small" @click="clearFilter">{{ $t('item.kanban_filter_clear') }}</a-button>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import type { Dayjs } from 'dayjs'

interface Props {
  itemInfo: any
  members: any[]
  allTags: string[]
}

defineProps<Props>()
const emit = defineEmits<{ filter: [filters: any] }>()

const filters = reactive<any>({})
const dueDateRange = ref<[Dayjs | string, Dayjs | string] | null>(null)

const onDueDateRangeChange = (dates: string[] | null) => {
  if (dates && dates.length === 2) {
    filters.due_date_start = dates[0]
    filters.due_date_end = dates[1]
  } else {
    delete filters.due_date_start
    delete filters.due_date_end
  }
  emitFilter()
}

const emitFilter = () => {
  const cleaned: any = {}
  Object.keys(filters).forEach(k => {
    if (filters[k] !== undefined && filters[k] !== null && filters[k] !== '') {
      cleaned[k] = filters[k]
    }
  })
  emit('filter', cleaned)
}

const clearFilter = () => {
  Object.keys(filters).forEach(k => delete filters[k])
  dueDateRange.value = null
  emit('filter', {})
}
</script>

<style lang="scss" scoped>
.filter-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 24px;
  background: var(--color-obvious);
  border-bottom: 1px solid var(--color-border);
  position: fixed;
  top: 139px;
  left: 0;
  right: 0;
  z-index: 997;
}
</style>
