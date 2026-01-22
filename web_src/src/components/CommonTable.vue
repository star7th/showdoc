<!--
  CommonTable - 通用表格组件

  【分页模式说明】

  CommonTable 支持两种分页模式：

  1. 服务端分页（默认）- dataSource="server"
     - 数据由后端分页返回，组件直接展示
     - 适用于大数据量场景（如管理后台列表）
     - 使用方式：
       - 父组件根据 pagination.current 和 pagination.pageSize 请求后端
       - 后端返回当前页数据和总数
       - 父组件将当前页数据赋值给 tableData
       - 组件直接展示 tableData，不再进行前端分页

  2. 客户端分页 - dataSource="client"
     - 组件在前端对数据进行分页切片
     - 适用于小数据量场景（如项目选择器）
     - 使用方式：
       - 父组件一次性获取所有数据
       - 组件根据 pagination 配置在前端进行分页切片

  【使用示例】

  <template>
    <CommonTable
      :table-header="tableHeader"
      :table-data="userList"
      :pagination="pagination"
      :loading="loading"
      row-key="uid"
      @page-change="handlePageChange"
    >
      <template #cell-role="{ row }">
        <span>{{ row.role }}</span>
      </template>
    </CommonTable>
  </template>

  <script setup lang="ts">
  import { ref, reactive } from 'vue'
  import CommonTable from '@/components/CommonTable.vue'

  const userList = ref([])
  const pagination = reactive({
    current:1,
    pageSize: 10,
    total: 0
  })

  const tableHeader = [
    { title: '用户名', key: 'username', width: 140 },
    { title: '姓名', key: 'name', width: 100 },
    { title: '操作', key: 'action', width: 200, center: true }
  ]

  const handlePageChange = (page: number, pageSize: number) => {
    pagination.current = page
    pagination.pageSize = pageSize
    fetchUserList()
  }
  </script>

-->
<template>
  <div class="common-table-wrapper" :class="{ 'with-pagination': pagination }">
    <!-- 表头插槽：由父组件决定是否渲染 -->
    <header class="text-secondary" v-if="$slots.renderHeader">
      <slot name="renderHeader"></slot>
    </header>
    
    <main :style="{ maxHeight }">
      <div v-if="loading" class="loading-container">
        <div class="loading-text">{{ t('common.loading') }}</div>
      </div>
      <div v-else-if="displayData.length === 0" class="empty-container">
        <div class="empty-text">{{ emptyTextDisplay }}</div>
      </div>
      <div
        v-else
        class="table-container"
      >
        <table>
          <thead>
            <tr>
              <!-- 行选择列 -->
              <th v-if="rowSelection" class="selection-col">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  :indeterminate="indeterminate"
                  @change="handleSelectAll"
                />
              </th>
              <th v-for="(item, index) in props.tableHeader" :key="index"
                :style="item.width ? { 'min-width': item.width + 'px', width: item.width + 'px' } : {}"
                :class="{ center: item.center }"
              >
                {{ item.title }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
        v-for="(data, index) in displayData"
        :key="getRowKey(data, index)"
        :class="{ 'row-selected': isRowSelected(data, index), 'row-clickable': rowClickable }"
        @click="handleRowClick(data, index)"
      >
        <!-- 行选择列 -->
              <td v-if="rowSelection" class="selection-col" @click.stop>
          <input
            type="checkbox"
            :checked="isRowSelected(data, index)"
            @change="handleRowSelect(data, index, $event)"
          />
              </td>
              <td
          v-for="(item, num) in props.tableHeader"
          :key="`${index}-${num}`"
          :style="item.width ? { 'min-width': item.width + 'px', width: item.width + 'px' } : {}"
          :class="{ center: item.center }"
        >
          <!-- 自定义单元格渲染 -->
          <slot
            :name="`cell-${item.key}`"
            :row="data"
            :column="item"
            :index="index"
            :value="data[item.key]"
          >
            <!-- 原有的 tools 功能 -->
            <div class="tools" v-if="item.tools">
              <div
                class="tools-item clickable"
                v-for="(tool, i) in data.tools"
                :key="`${index}-${num}-${i}`"
                @click="tool.onclick(data)"
              >
                {{ tool.name }}
              </div>
            </div>
            <!-- 默认显示 -->
            <template v-else>
              {{ data[item.key] }}
            </template>
          </slot>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
    <!-- 分页器 -->
    <div class="pagination-container" v-if="pagination">
      <div class="pagination-info">
        {{ t('common.pagination_info', { start: paginationStart, end: paginationEnd, total: pagination.total }) }}
      </div>
      <div class="pagination-controls">
        <button
          class="pagination-btn"
          :disabled="pagination.current === 1"
          @click="handlePageChange(pagination.current - 1)"
        >
          {{ t('common.prev_page') }}
        </button>
        <span class="pagination-page">
          {{ pagination.current }} / {{ totalPages }}
        </span>
        <button
          class="pagination-btn"
          :disabled="pagination.current >= totalPages"
          @click="handlePageChange(pagination.current + 1)"
        >
          {{ t('common.next_page') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface TableHeaderItem {
  title: string;
  key: string;
  width?: number;
  center?: boolean;
  tools?: boolean;
}

interface PaginationConfig {
  current: number;
  pageSize: number;
  total: number;
}

interface RowSelectionConfig {
  selectedRowKeys: (string | number)[];
  onChange?: (selectedRowKeys: (string | number)[], selectedRows: any[]) => void;
  getCheckboxProps?: (record: any) => { disabled?: boolean };
}

const props = withDefaults(
  defineProps<{
    tableHeader: TableHeaderItem[];
    tableData: any[];
    pagination?: PaginationConfig | false;
    rowSelection?: RowSelectionConfig | false;
    loading?: boolean;
    rowKey?: string | ((record: any, index: number) => string | number);
    rowClickable?: boolean;
    maxHeight?: string;
    typeField?: string;
    typeFieldMapping?: Record<string, string>;
    emptyText?: string | (() => string);
    dataSource?: 'client' | 'server';
  }>(),
  {
    pagination: false,
    rowSelection: false,
    loading: false,
    rowClickable: false,
    maxHeight: 'calc(100vh - 400px)',
    typeField: '',
    typeFieldMapping: () => ({}),
    dataSource: 'server',
  }
);

const emit = defineEmits<{
  (e: 'rowClick', record: any, index: number): void;
  (e: 'pageChange', page: number, pageSize: number): void;
  (e: 'selectionChange', selectedRowKeys: (string | number)[], selectedRows: any[]): void;
}>();

// 分页相关
const paginationConfig = computed(() => {
  if (!props.pagination) return null;
  return props.pagination as PaginationConfig;
});

const totalPages = computed(() => {
  if (!paginationConfig.value) return 1;
  return Math.ceil(paginationConfig.value.total / paginationConfig.value.pageSize);
});

const paginationStart = computed(() => {
  if (!paginationConfig.value) return 1;
  return (paginationConfig.value.current - 1) * paginationConfig.value.pageSize + 1;
});

const paginationEnd = computed(() => {
  if (!paginationConfig.value) return props.tableData.length;
  const end = paginationConfig.value.current * paginationConfig.value.pageSize;
  return Math.min(end, paginationConfig.value.total);
});

// 空状态文本
const emptyTextDisplay = computed(() => {
  if (typeof props.emptyText === 'function') {
    return props.emptyText();
  }
  return props.emptyText || t('common.no_data');
});

// 显示的数据（分页后的数据）
const displayData = computed(() => {
  // 服务端分页：直接返回后端返回的数据
  if (props.dataSource === 'server') {
    return props.tableData;
  }

  // 客户端分页：在前端进行分页切片
  if (!paginationConfig.value) {
    return props.tableData;
  }
  const { current, pageSize } = paginationConfig.value;
  const start = (current - 1) * pageSize;
  const end = start + pageSize;
  return props.tableData.slice(start, end);
});

// 行选择相关
const rowSelectionConfig = computed(() => {
  if (!props.rowSelection) return null;
  return props.rowSelection as RowSelectionConfig;
});

const selectedRowKeys = computed(() => {
  if (!rowSelectionConfig.value) return [];
  return rowSelectionConfig.value.selectedRowKeys || [];
});

const allSelected = computed(() => {
  if (!rowSelectionConfig.value || displayData.value.length === 0) return false;
  return displayData.value.every((row, index) => isRowSelected(row, index));
});

const indeterminate = computed(() => {
  if (!rowSelectionConfig.value) return false;
  const selectedCount = displayData.value.filter((row, index) => isRowSelected(row, index)).length;
  return selectedCount > 0 && selectedCount < displayData.value.length;
});

function getRowKey(record: any, index: number): string | number {
  if (typeof props.rowKey === 'function') {
    return props.rowKey(record, index);
  }
  if (typeof props.rowKey === 'string') {
    return record[props.rowKey];
  }
  return index;
}

function isRowSelected(record: any, index: number): boolean {
  if (!rowSelectionConfig.value) return false;
  const key = getRowKey(record, index);
  return selectedRowKeys.value.includes(key);
}

function handleRowSelect(record: any, index: number, event: Event) {
  if (!rowSelectionConfig.value) return;
  const checked = (event.target as HTMLInputElement).checked;
  const key = getRowKey(record, index);
  // 先复制已选中的项
  let newSelectedKeys: (string | number)[] = [...selectedRowKeys.value];

  if (checked) {
    if (!newSelectedKeys.includes(key)) {
      newSelectedKeys.push(key);
    }
  } else {
    newSelectedKeys = newSelectedKeys.filter((k) => k !== key);
  }

  const selectedRows = props.tableData.filter((row, idx) => {
    const rowKey = getRowKey(row, idx);
    return newSelectedKeys.includes(rowKey);
  });

  if (rowSelectionConfig.value.onChange) {
    rowSelectionConfig.value.onChange(newSelectedKeys, selectedRows);
  }
  emit('selectionChange', newSelectedKeys, selectedRows);
}

function handleSelectAll(event: Event) {
  if (!rowSelectionConfig.value) return;
  const checked = (event.target as HTMLInputElement).checked;
  let newSelectedKeys: (string | number)[] = [];

  if (checked) {
    newSelectedKeys = displayData.value.map((row, index) => getRowKey(row, index));
    // 合并已有的选中项
    newSelectedKeys = Array.from(new Set([...selectedRowKeys.value, ...newSelectedKeys]));
  } else {
    // 只保留不在当前页的选中项
    const currentPageKeys = displayData.value.map((row, index) => getRowKey(row, index));
    newSelectedKeys = selectedRowKeys.value.filter((key) => !currentPageKeys.includes(key));
  }

  const selectedRows = props.tableData.filter((row, idx) => {
    const rowKey = getRowKey(row, idx);
    return newSelectedKeys.includes(rowKey);
  });

  if (rowSelectionConfig.value.onChange) {
    rowSelectionConfig.value.onChange(newSelectedKeys, selectedRows);
  }
  emit('selectionChange', newSelectedKeys, selectedRows);
}

function handleRowClick(record: any, index: number) {
  if (props.rowClickable) {
    emit('rowClick', record, index);
  }
}

function handlePageChange(page: number) {
  if (!paginationConfig.value) return;
  emit('pageChange', page, paginationConfig.value.pageSize);
}
</script>

<style scoped lang="scss">
.common-table-wrapper {
  width: 100%;
  height: 100%;
  font-size: var(--font-size-s);
  font-weight: 600;
  overflow-y: hidden;
  display: flex;
  flex-direction: column;

  main {
    overflow-y: auto;
    overflow-x: hidden;
  }
}

.table-container {
  overflow-x: auto;
  flex: 1;
}

table {
  width: 100%;
  table-layout: fixed;
  border-collapse: collapse;
  font-size: var(--font-size-s);
}

thead {
  tr {
    background-color: transparent;
  }

  th {
    padding: 12px;
    text-align: left;
    font-weight: 600;
    background-color: var(--color-obvious);
    color: var(--color-text-primary);
    border-bottom: 1px solid var(--color-inactive);

    &.selection-col {
      width: 40px;
      min-width: 40px;
      text-align: center;
  }

    &.center {
      display: table-cell;
      text-align: center;
    }
  }
}

tbody {
  tr {
    border-bottom: 1px solid var(--color-inactive);
    transition: background-color 0.15s ease;

    &.row-clickable {
      cursor: pointer;

      &:hover {
        background-color: var(--hover-overlay);
      }
    }

    &.row-selected {
      background-color: var(--color-obvious);
    }
  }

  td {
    padding: 12px;
    color: var(--color-text-primary);

    &.selection-col {
      width: 40px;
      min-width: 40px;
      text-align: center;
    }

    &.center {
      display: table-cell;
      text-align: center;
    }
    }
  }

  .loading-container,
  .empty-container {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 0;
    color: var(--color-text);
  min-height: 200px;
    text-align: center;
  }

  .loading-text,
  .empty-text {
    color: var(--color-grey);
    line-height: 1.8;
    max-width: 600px;
    text-align: center;
}

.tools {
  display: flex;
  justify-content: center;
}

.tools-item {
  color: var(--color-active);
  margin: 0 10px;
  cursor: pointer;

  &:hover {
    color: var(--color-active);
  }
}

.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0 0;
  margin-top: 16px;
  border-top: 1px solid var(--color-interval);
  flex-shrink: 0;

  .pagination-info {
    color: var(--color-text);
    font-size: var(--font-size-s);
  }

  .pagination-controls {
    display: flex;
    align-items: center;
    gap: 12px;
  }
  }

  .pagination-btn {
  padding: 6px 16px;
    border: 1px solid var(--color-interval);
  background-color: var(--color-default);
  color: var(--color-text-primary);
    border-radius: 6px;
    cursor: pointer;
    font-size: var(--font-size-s);
    transition: all 0.15s ease;

    &:hover:not(:disabled) {
    background-color: var(--hover-overlay);
      border-color: var(--color-primary);
    }

    &:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
  }

  .pagination-page {
    color: var(--color-text);
    font-size: var(--font-size-s);
    min-width: 60px;
    text-align: center;
}

// 亮色主题
.type-badge.member {
  background-color: #e6f7ff;
}

.type-badge.team {
  background-color: #52c41a;
}

// 暗黑主题
[data-theme="dark"] {
  thead {
    tr {
      background-color: transparent;
    }

    th {
      background-color: #3a3a3a;
      color: #e0e0e0;
      border-bottom-color: #3a3a3a;

      &.center {
        display: table-cell;
        text-align: center;
      }
    }
  }

  tbody {
    tr {
      border-bottom-color: #3a3a3a;

      &.row-clickable {
        &:hover {
          background-color: #3a3a3a;
        }
      }

      &.row-selected {
        background-color: #2d2d2d;
      }
    }

    td {
      color: #e0e0e0;

      &.center {
        display: table-cell;
        text-align: center;
      }
    }
  }

  .loading-container,
  .empty-container {
    color: #e0e0e0;
  }

  .loading-text,
  .empty-text {
    color: #888;
  }

  .pagination-container {
    border-top-color: #3a3a3a;

    .pagination-info {
      color: #e0e0e0;
    }

    .pagination-btn {
      background-color: #2d2d2d;
      color: #e0e0e0;
      border-color: #3a3a3a;

      &:hover:not(:disabled) {
        background-color: #3a3a3a;
        border-color: var(--color-primary);
      }
    }

    .pagination-page {
      color: #e0e0e0;
    }
  }
}

// 全局未作用域样式
:global(.common-table-wrapper) {
  table,
  thead,
  tbody,
  th,
  td {
    transition: all 0.15s ease;
  }
}

[data-theme="dark"] :global(.common-table-wrapper) {
  table {
    tbody {
      tr {
        td {
          a {
            color: #40a9ff;

            &:hover {
              color: #1890ff;
            }
          }
        }
      }
    }
  }
}
</style>
