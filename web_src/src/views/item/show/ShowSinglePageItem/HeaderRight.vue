<template>
  <div class="header-right">
    <!-- 编辑页面 -->
    <div v-if="itemInfo?.item_edit == 1" class="icon-item" @click="handleEdit">
      <a-tooltip :title="$t('page.edit_page')" placement="bottom">
        <i class="far fa-edit"></i>
      </a-tooltip>
    </div>

    <!-- 分享 -->
    <div class="icon-item" @click="handleShare">
      <a-tooltip :title="$t('item.share')" placement="bottom">
        <i class="far fa-share-nodes"></i>
      </a-tooltip>
    </div>

    <!-- 成员管理 -->
    <div
      v-if="itemInfo?.item_manage == 1"
      class="icon-item"
      @click="handleMember"
    >
      <a-tooltip :title="$t('item.member_manage')" placement="bottom">
        <i class="fal fa-users"></i>
      </a-tooltip>
    </div>

    <!-- 登录 -->
    <div v-if="itemInfo?.is_login != 1" class="icon-item" @click="handleLogin">
      <a-tooltip :title="$t('user.login')" placement="bottom">
        <i class="far fa-user"></i>
      </a-tooltip>
    </div>

    <!-- 导出 -->
    <div
      v-if="itemInfo?.item_edit == 1 && itemInfo?.item_manage != 1"
      class="icon-item"
      @click="handleExport"
    >
      <a-tooltip :title="$t('item.export')" placement="bottom">
        <i class="far fa-arrow-down-to-bracket"></i>
      </a-tooltip>
    </div>

    <!-- 语言切换（仅游客显示） -->
    <a-tooltip
      v-if="itemInfo?.is_login != 1"
      :title="$t('header.switch_language')"
      placement="bottom"
    >
      <div class="icon-item language-item">
        <LanguageToggle />
      </div>
    </a-tooltip>

    <!-- 主题切换 -->
    <a-tooltip
      :title="
        appStore.theme === 'light'
          ? $t('common.dark_mode')
          : $t('common.light_mode')
      "
      placement="bottom"
    >
      <div class="icon-item theme-toggle-item" @click="handleToggleTheme">
        <i class="fas fa-circle-half-stroke"></i>
      </div>
    </a-tooltip>

    <!-- 更多菜单 -->
    <CommonDropdownMenu
      v-if="itemInfo?.item_manage == 1"
      :list="menuItems"
      trigger="hover"
      placement="bottom"
      :offsetX="-80"
      @select="handleDropdownSelect"
    >
      <div class="icon-item">
        <i class="far fa-ellipsis"></i>
      </div>
    </CommonDropdownMenu>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Message from '@/components/Message'
import CommonDropdownMenu from '@/components/CommonDropdownMenu.vue'
import { useAppStore } from '@/store'
import ShareModal from '@/views/modals/item/ShareModal/index'
import MemberModal from '@/views/modals/item/MemberModal/index'
import CreateItemModal from '@/views/modals/item/CreateItemModal'
import ExportFileModal from '@/views/modals/item/ExportFileModal/index'
import ChangeLogModal from '@/views/modals/item/ChangeLogModal/index'
import ArchiveModal from '@/views/modals/item/ArchiveModal/index'
import AttornModal from '@/views/modals/item/AttornModal/index'
import DeleteModal from '@/views/modals/item/DeleteModal/index'
import HistoryModal from '@/views/modals/page/HistoryModal/index'
import LanguageToggle from '@/components/LanguageToggle.vue'

// Props
interface Props {
  itemInfo: any
  pageId: number
  pageInfo?: any
}

const props = withDefaults(defineProps<Props>(), {
  itemInfo: () => ({}),
  pageId: 0,
  pageInfo: () => ({}),
})

// Emits
const emit = defineEmits<{
  editPage: []
  reload: []
}>()

// Composables
const router = useRouter()
const { t } = useI18n()
const appStore = useAppStore()

// Methods
const handleToggleTheme = () => {
  appStore.toggleTheme()
}

// 下拉菜单项
const menuItems = computed(() => {
  const list = [
    {
      value: 'export',
      icon: ['far', 'fa-arrow-down-to-bracket'],
      text: t('item.export'),
    },
  ]

  // 如果有 pageId，添加历史版本
  if (props.pageId) {
    list.push({
      value: 'history',
      icon: ['far', 'fa-rectangle-history'],
      text: t('page.page_history_version'),
    })
  }

  list.push(
    {
      value: 'update',
      icon: ['far', 'fa-edit'],
      text: t('item.updateBaseInfo'),
    },
    {
      value: 'attorn',
      icon: ['far', 'fa-recycle'],
      text: t('item.attorn'),
    },
    {
      value: 'changelog',
      icon: ['far', 'fa-clock-rotate-left'],
      text: t('item.change_log'),
    },
    {
      value: 'archive',
      icon: ['far', 'fa-box-archive'],
      text: t('item.archive'),
    },
    {
      value: 'delete',
      icon: ['far', 'fa-trash-can'],
      text: t('item.delete_item'),
    }
  )

  return list
})

// Methods
const handleEdit = () => {
  if (props.pageId) {
    router.push(`/page/edit/${props.itemInfo?.item_id}/${props.pageId}`)
  }
  emit('editPage')
}

const handleShare = async () => {
  if (props.itemInfo) {
    await ShareModal({
      item_domain: props.itemInfo.item_domain,
      item_id: props.itemInfo.item_id,
      page_id: props.pageId,
      page_unique_key: props.pageInfo?.unique_key,
      page_title: props.pageInfo?.page_title,
      item_info: props.itemInfo,
    })
  }
}

const handleMember = async () => {
  if (props.itemInfo) {
    await MemberModal(props.itemInfo.item_id)
  }
}

const handleLogin = () => {
  router.push('/user/login')
}

const handleExport = () => {
  // TODO: 实现导出功能
  Message.info(t('common.coming_soon'))
}

const handleDropdownSelect = async (item: any) => {
  const key = item.value
  switch (key) {
    case 'export':
      if (props.itemInfo) {
        await ExportFileModal(props.itemInfo.item_id)
      }
      break

    case 'history':
      if (props.pageId) {
        await HistoryModal({
          pageId: props.pageId,
          allowRecover: false, // 展示页不允许恢复版本
          allowEdit: false, // 展示页不允许编辑备注
        })
      }
      break

    case 'update':
      if (props.itemInfo) {
        await CreateItemModal({
          item_id: props.itemInfo.item_id,
        })
      }
      break

    case 'attorn':
      if (props.itemInfo) {
        await AttornModal(props.itemInfo.item_id)
      }
      break

    case 'changelog':
      if (props.itemInfo) {
        await ChangeLogModal(props.itemInfo.item_id)
      }
      break

    case 'archive':
      if (props.itemInfo) {
        await ArchiveModal(props.itemInfo.item_id)
      }
      break

    case 'delete':
      if (props.itemInfo) {
        await DeleteModal(props.itemInfo.item_id)
      }
      break
  }
}
</script>

<style scoped lang="scss">
.header-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

// 图标按钮（克制设计，与常规项目保持一致）
.icon-item {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--color-bg-primary);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0; // 防止收缩，保持正方形

  &:hover {
    background-color: var(--hover-overlay);
    box-shadow: var(--shadow-sm);
  }

  i {
    color: var(--color-text-primary);
    font-size: 16px;
    // 图标颜色保持不变（克制设计）
  }
}

// 主题切换按钮（克制设计）
.theme-toggle-item {
  i {
    color: var(--color-orange);
  }

  &:hover i {
    color: var(--color-orange);
  }
}

// 语言切换按钮样式
.language-item {
  :deep(.language-toggle) {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 0;
    width: 100%;
    height: 100%;
    background: none;
    box-shadow: none;
    transition: none;

    .language-text {
      font-size: 13px;
      font-weight: 500;
      line-height: 1;
    }

    .language-icon {
      font-size: 14px;
    }

    &:hover {
      background-color: var(--hover-overlay);
      box-shadow: var(--shadow-sm);
    }
  }
}
</style>
