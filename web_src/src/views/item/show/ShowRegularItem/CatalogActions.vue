<template>
  <div class="catalog-actions" v-if="itemId">
    <a-tooltip :title="$t('page.new_page')" placement="top">
      <div class="action-item" @click="handleCreatePage">
        <i class="far fa-plus"></i>
      </div>
    </a-tooltip>

    <a-tooltip :title="$t('page.copy_page')" placement="top">
      <div class="action-item" @click="handleCopyPage">
        <i class="far fa-clone"></i>
      </div>
    </a-tooltip>

    <a-tooltip :title="$t('catalog.add_cat')" placement="top">
      <div class="action-item" @click="handleCreateCatalog">
        <i class="far fa-folder-plus"></i>
      </div>
    </a-tooltip>

    <!-- 更多菜单 - 使用公共组件 -->
    <div class="action-item more-action-wrapper">
      <CommonDropdownMenu
        :list="menuList"
        trigger="hover"
        placement="top"
        @select="handleMenuSelect"
      >
        <!-- 自定义触发按钮 - hover 模式下不使用 tooltip 避免冲突 -->
        <div class="more-trigger">
        <i class="far fa-ellipsis"></i>
      </div>
      </CommonDropdownMenu>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import request from '@/utils/request'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import PromptModal from '@/components/PromptModal/index'
import RecycleModal from '@/views/modals/item/RecycleModal/index'
import HistoryModal from '@/views/modals/page/HistoryModal/index'
import EditPageModal from '@/views/modals/page/EditPageModal/index'
import CommonDropdownMenu from '@/components/CommonDropdownMenu.vue'
import type { DropdownMenuItem } from '@/components/CommonDropdownMenu.vue'

// Props
interface Props {
  itemId?: number
  pageId?: number
  pageInfo?: any
  searchItem?: (keyword: string) => void
  expandAll?: () => void
  collapseAll?: () => void
}

const props = withDefaults(defineProps<Props>(), {
  itemId: 0,
  pageId: 0,
  pageInfo: () => ({}),
  searchItem: () => {},
  expandAll: () => {},
  collapseAll: () => {}
})

// Emits
const emit = defineEmits<{
  reloadItem: []
}>()

// Composables
const { t } = useI18n()

// Computed - 菜单列表
const menuList = computed<DropdownMenuItem[]>(() => {
  const list: DropdownMenuItem[] = [
    {
      icon: ['far', 'fa-recycle'],
      text: t('item.recycle'),
      value: 'recycle'
    }
  ]

  if (props.pageId) {
    list.push(
      {
        icon: ['far', 'fa-clock-rotate-left'],
        text: t('page.page_history_version'),
        value: 'history'
      },
      {
        icon: ['far', 'fa-circle-info'],
        text: t('page.page_info'),
        value: 'info'
      }
    )
  }

  list.push(
    {
      icon: ['far', 'fa-folder-open'],
      text: t('catalog.expand_all'),
      value: 'expand-all'
    },
    {
      icon: ['far', 'fa-folder-closed'],
      text: t('catalog.collapse_all'),
      value: 'collapse-all'
    }
  )

  return list
})

// Methods
const handleCreatePage = async () => {
  if (!props.itemId) {
    Message.info(t('common.please_select_item'))
    return
  }

  try {
    const result = await EditPageModal({
      itemId: props.itemId,
      editPageId: 0,
      copyPageId: 0
    })

    if (result) {
      emit('reloadItem')
    }
  } catch (error) {
    console.error('打开页面编辑器失败:', error)
  }
}

const handleCopyPage = async () => {
  if (!props.pageId) {
    Message.info(t('common.please_select_page'))
    return
  }

  try {
    const result = await EditPageModal({
      itemId: props.itemId || 0,
      editPageId: 0,
      copyPageId: props.pageId
    })

    if (result) {
      emit('reloadItem')
    }
  } catch (error) {
    console.error('复制页面失败:', error)
  }
}

const handleCreateCatalog = async () => {
  const catName = await PromptModal(
    t('catalog.add_cat'),
    '',
    t('catalog.cat_name')
  )
  
  if (!catName || !catName.trim()) return
  
  createCatalog(catName.trim(), 0)
}

// 处理菜单选择
const handleMenuSelect = async (item: DropdownMenuItem) => {
  switch (item.value) {
    case 'recycle':
      if (props.itemId) {
        await RecycleModal(props.itemId)
      }
      break
    case 'history':
      if (props.pageId) {
        await HistoryModal({
          pageId: props.pageId,
          allowRecover: false,  // 项目展示页不允许恢复版本
          allowEdit: false      // 项目展示页不允许编辑备注
        })
      }
      break
    case 'info':
      handleShowPageInfo()
      break
    case 'expand-all':
      props.expandAll()
      break
    case 'collapse-all':
      props.collapseAll()
      break
  }
}

const createCatalog = async (catName: string, parentCatId: number) => {
  try {
    const result = await request('/api/catalog/save', {
      item_id: props.itemId,
      cat_id: 0,
      parent_cat_id: parentCatId,
      cat_name: catName
    }, 'post', false)
    
    if (result.error_code === 0) {
      Message.success(t('common.save_success'))
      emit('reloadItem')
    } else {
      await AlertModal(result.error_message || t('common.save_failed'))
    }
  } catch (error) {
    console.error('创建目录失败:', error)
    await AlertModal(t('common.save_failed'))
  }
}

const handleShowPageInfo = async () => {
  if (!props.pageInfo) return
  
  const html = `本页面由 ${props.pageInfo.author_username} 于 ${props.pageInfo.addtime} 更新`
  await AlertModal(html, { dangerouslyUseHTMLString: true })
}
</script>

<style scoped lang="scss">
.catalog-actions {
  position: relative;
  height: 40px;
  display: flex;
  align-items: center;
  gap: 6px;
  background-color: var(--color-bg-secondary);
  border-top: 1px solid var(--color-border);
  border-radius: 0 0 8px 8px;
  z-index: 10;
  padding: 0 8px;
  flex-shrink: 0; // 防止被压缩
}

.action-item {
  flex: 1;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--color-text-secondary);
  transition: all 0.15s ease;
  border-radius: 4px;

  &:hover {
    background: var(--hover-overlay);
    color: var(--color-active);
  }

  i {
    font-size: 14px;
  }
}

// 更多按钮包装器
.more-action-wrapper {
  flex: 1;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: var(--color-text-secondary);
  transition: all 0.15s ease;
  border-radius: 4px;
  
  &:hover {
    background: var(--hover-overlay);
    color: var(--color-active);
  }

  i {
    font-size: 14px;
  }
}

// 更多按钮触发器
.more-trigger {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;

  i {
    color: inherit;
  }
}
</style>
