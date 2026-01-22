<template>
  <div class="create-item-btn-div">
    <div class="left">
      <div @click="regularItem" class="create-item-left-btn">
        <i class="mr-3 fas fa-plus"></i>
        <span>{{ t('item.create_new_item') }}</span>
      </div>
    </div>
    <div class="right">
      <SDropdown
        :title="t('item.create_new_item')"
        titleIcon="fas fa-plus"
        :menuListGroup="menuListGroup"
        placement="top"
      >
        <div class="create-item-right-btn">
          <i class="fas fa-ellipsis"></i>
        </div>
      </SDropdown>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import SDropdown from '@/components/SDropdown.vue'
import CreateItemModal from '@/views/modals/item/CreateItemModal'
import ImportOpenApiModal from '@/views/modals/item/ImportOpenApiModal'
import ImportFileModal from '@/views/modals/item/ImportFileModal'

const { t } = useI18n()

const props = defineProps<{
  itemGroupId: number
  callback: () => void
}>()

// 数据状态
const menuListGroup = ref<any[]>([])

// 创建常规项目
const regularItem = async () => {
  const result = await CreateItemModal({ item_type: '1', item_group_id: props.itemGroupId })
  if (result) {
    props.callback()
  }
}

// 创建单页项目
const singleItem = async () => {
  const result = await CreateItemModal({ item_type: '2', item_group_id: props.itemGroupId })
  if (result) {
    props.callback()
  }
}

// 创建表格项目
const tableItem = async () => {
  const result = await CreateItemModal({ item_type: '4', item_group_id: props.itemGroupId })
  if (result) {
    props.callback()
  }
}

// 创建白板项目
const whiteboardItem = async () => {
  const result = await CreateItemModal({ item_type: '5', item_group_id: props.itemGroupId })
  if (result) {
    props.callback()
  }
}

// 导入文件
const importFile = async () => {
  const result = await ImportFileModal()
  if (result) {
    props.callback()
  }
}

// OpenAPI 自动创建
const autoCreate = async () => {
  const result = await ImportOpenApiModal()
  if (result) {
    props.callback()
  }
}

// 初始化菜单数据
menuListGroup.value = [
  {
    group_name: t('item.create'),
    listMenu: [
      {
        title: t('item.regular_item'),
        icon: 'fas fa-notes',
        desc: t('item.regular_item_desc'),
        method: regularItem
      },
      {
        title: t('item.single_item'),
        icon: 'fas fa-file',
        desc: t('item.single_item_desc'),
        method: singleItem
      },
      {
        title: t('item.table_item'),
        icon: 'fas fa-table',
        desc: t('item.table_item_desc'),
        method: tableItem
      },
      {
        title: t('item.whiteboard_item'),
        icon: 'fas fa-pen',
        desc: t('item.whiteboard_item_desc'),
        method: whiteboardItem
      }
    ]
  },

  {
    group_name: t('item.import'),
    listMenu: [
      {
        title: t('item.import_file'),
        icon: 'fas fa-upload',
        desc: t('item.import_file_desc'),
        method: importFile
      },
      {
        title: t('item.auto_create'),
        icon: 'fas fa-terminal',
        desc: t('item.auto_create_desc'),
        method: autoCreate
      }
    ]
  }
]
</script>

<style scoped lang="scss">
// 创建按钮（克制设计）
.create-item-btn-div {
  width: 200px;
  height: 54px;
  bottom: 24px;
  position: fixed;
  box-shadow: var(--shadow-base);
  border-radius: 8px;
  background: var(--color-bg-primary);
  border: 1px solid var(--color-border);
  font-weight: 500;
  left: 50%;
  transform: translateX(-50%);
  z-index: 100;
  margin-left: 115px;
  transition: all 0.15s ease;
  
  &:hover {
    box-shadow: var(--shadow-lg);
  }
}

/* 卡片视图模式下按钮位置调整 */
.card-view-mode .create-item-btn-div {
  margin-left: 100px;
}

.create-item-btn-div .left,
.create-item-btn-div .right {
  height: 54px;
  display: inline-block;
}

.create-item-left-btn {
  width: 135px;
  height: 54px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-right: 1px solid var(--color-border);
  border-radius: 8px 0 0 8px;
  cursor: pointer;
  transition: all 0.15s ease;

  &:hover {
    background-color: var(--hover-overlay);
  }
}

.create-item-right-btn {
  width: 60px;
  height: 54px;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 0 8px 8px 0;
  cursor: pointer;
  transition: all 0.15s ease;

  &:hover {
    background-color: var(--hover-overlay);
  }
}
</style>
