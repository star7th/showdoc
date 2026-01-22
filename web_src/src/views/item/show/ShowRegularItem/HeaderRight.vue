<template>
  <div class="header-right">
    <!-- 编辑页面 -->
    <a-tooltip v-if="itemInfo?.item_edit == 1 && pageId" :title="$t('page.edit_page')" placement="bottom">
      <div class="icon-item" @click="handleEdit">
        <i class="far fa-edit"></i>
      </div>
    </a-tooltip>

    <!-- RunApi 项目提示 -->
    <a-tooltip v-if="itemInfo?.item_type == 3 && itemInfo?.is_login" :title="$t('item.runapi_edit_hint')" placement="bottom">
      <div class="icon-item disabled">
        <i class="far fa-edit"></i>
      </div>
    </a-tooltip>

    <!-- 分享 -->
    <a-tooltip :title="$t('item.share')" placement="bottom">
      <div class="icon-item" @click="handleShare">
        <i class="far fa-share-nodes"></i>
      </div>
    </a-tooltip>

    <!-- 消息通知 -->
    <a-tooltip v-if="itemInfo?.is_login" :title="$t('message.my_notice')" placement="bottom">
      <a-badge :count="newMsg ? 'New' : 0" :offset="[-5, 5]">
        <div class="icon-item" @click="handleMessage">
          <i class="far fa-message"></i>
        </div>
      </a-badge>
    </a-tooltip>

    <!-- 用户中心 -->
    <a-tooltip v-if="itemInfo?.is_login" :title="$t('user.user_center')" placement="bottom">
      <div class="icon-item" @click="handleUserCenter">
        <i class="far fa-user"></i>
      </div>
    </a-tooltip>

    <!-- 登录 -->
    <a-tooltip v-if="!itemInfo?.is_login" :title="$t('user.login')" placement="bottom">
      <div class="icon-item" @click="handleLogin">
        <i class="far fa-user"></i>
      </div>
    </a-tooltip>

    <!-- 关于 ShowDoc -->
    <a-tooltip v-if="!itemInfo?.is_login" :title="$t('item.aboutShowdoc')" placement="bottom">
      <div class="icon-item" @click="handleAboutShowdoc">
        <i class="far fa-circle-info"></i>
      </div>
    </a-tooltip>

    <!-- 语言切换（仅游客显示） -->
    <a-tooltip v-if="!itemInfo?.is_login" :title="$t('header.switch_language')" placement="bottom">
      <div class="icon-item language-item">
        <LanguageToggle />
      </div>
    </a-tooltip>

    <!-- 主题切换 -->
    <a-tooltip :title="appStore.theme === 'light' ? $t('common.dark_mode') : $t('common.light_mode')" placement="bottom">
      <div class="icon-item theme-toggle-item" @click="handleToggleTheme">
        <i class="far fa-circle-half-stroke"></i>
      </div>
    </a-tooltip>

    <!-- 更多菜单 -->
    <CommonDropdownMenu
      v-if="itemInfo?.item_manage"
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
import { useAppStore, useUserStore } from '@/store'
import CommonDropdownMenu from '@/components/CommonDropdownMenu.vue'
import EditPageModal from '@/views/modals/page/EditPageModal/index'
import ShareModal from '@/views/modals/item/ShareModal/index'
import MemberModal from '@/views/modals/item/MemberModal/index'
import CreateItemModal from '@/views/modals/item/CreateItemModal'
import UserCenterModal from '@/views/modals/user/UserCenterModal/index'
import MessageModal from '@/views/modals/message/MessageModal/index'
import ImportFileModal from '@/views/modals/item/ImportFileModal/index'
import ExportFileModal from '@/views/modals/item/ExportFileModal/index'
import RecycleModal from '@/views/modals/item/RecycleModal/index'
import ChangeLogModal from '@/views/modals/item/ChangeLogModal/index'
import AiKnowledgeBaseModal from '@/views/modals/item/AiKnowledgeBaseModal/index'
import OpenApiModal from '@/views/modals/item/OpenApiModal/index'
import ArchiveModal from '@/views/modals/item/ArchiveModal/index'
import AttornModal from '@/views/modals/item/AttornModal/index'
import DeleteModal from '@/views/modals/item/DeleteModal/index'
import LanguageToggle from '@/components/LanguageToggle.vue'
import type { DropdownMenuItem } from '@/components/CommonDropdownMenu.vue'

// Props
interface Props {
  pageId?: number
  itemInfo?: any
  pageInfo?: any
  searchItem?: (keyword: string) => void
}

const props = withDefaults(defineProps<Props>(), {
  pageId: 0,
  itemInfo: () => ({}),
  pageInfo: () => ({}),
  searchItem: () => {}
})

// Emits
const emit = defineEmits<{
  editPage: []
}>()

// Composables
const router = useRouter()
const { t } = useI18n()
const appStore = useAppStore()
const userStore = useUserStore()

// Methods
const handleToggleTheme = () => {
  appStore.toggleTheme()
}

// Computed
const newMsg = computed(() => userStore.newMsg)

// 下拉菜单项
const menuItems = computed<DropdownMenuItem[]>(() => [
  {
    icon: ['far', 'fa-upload'],
    text: t('item.import'),
    value: 'import'
  },
  {
    icon: ['far', 'fa-download'],
    text: t('item.export'),
    value: 'export'
  },
  {
    icon: ['far', 'fa-users'],
    text: t('item.member_manage'),
    value: 'member'
  },
  {
    icon: ['far', 'fa-recycle'],
    text: t('item.recycle'),
    value: 'recycle'
  },
  {
    icon: ['far', 'fa-gear'],
    text: t('item.update_base_info'),
    value: 'setting'
  },
  {
    icon: ['far', 'fa-brain'],
    text: t('ai.ai_knowledge_base'),
    value: 'ai'
  },
  {
    icon: ['far', 'fa-terminal'],
    text: t('item.open_api'),
    value: 'openapi'
  },
  {
    icon: ['far', 'fa-clock-rotate-left'],
    text: t('item.change_log'),
    value: 'changelog'
  },
  {
    icon: ['far', 'fa-repeat'],
    text: t('item.attorn'),
    value: 'attorn'
  },
  {
    icon: ['far', 'fa-box-archive'],
    text: t('item.archive'),
    value: 'archive'
  },
  {
    icon: ['far', 'fa-trash-can'],
    text: t('item.delete_item'),
    value: 'delete'
  }
])

// 下拉菜单点击处理
const handleDropdownSelect = (item: DropdownMenuItem) => {
  const key = item.value
  handleMenuClick({ key })
}

// Methods
const handleEdit = async () => {
  if (props.pageId && props.itemInfo?.item_id) {
    const result = await EditPageModal({
      itemId: props.itemInfo.item_id,
      editPageId: props.pageId,
      copyPageId: 0
    })
    if (result) {
      emit('editPage')
    }
  }
}

const handleShare = async () => {
  if (props.itemInfo) {
    await ShareModal({
      item_domain: props.itemInfo.item_domain,
      item_id: props.itemInfo.item_id,
      page_id: props.pageId,
      page_unique_key: props.pageInfo?.unique_key,
      page_title: props.pageInfo?.page_title,
      item_info: props.itemInfo
    })
  }
}

const handleMessage = async () => {
  appStore.setNewMsg(0)
  await MessageModal()
}

const handleUserCenter = async () => {
  await UserCenterModal()
}

const handleLogin = () => {
  router.push('/user/login')
}

const handleAboutShowdoc = () => {
  window.open('https://www.showdoc.com.cn/help', '_blank')
}

const handleMenuClick = async ({ key }: { key: string }) => {
  switch (key) {
    case 'import':
      if (props.itemInfo) {
        await ImportFileModal({
          itemId: props.itemInfo.item_id
        })
      }
      break

    case 'export':
      if (props.itemInfo) {
        await ExportFileModal(props.itemInfo.item_id)
      }
      break

    case 'member':
      if (props.itemInfo) {
        await MemberModal(props.itemInfo.item_id)
      }
      break

    case 'recycle':
      if (props.itemInfo) {
        await RecycleModal(props.itemInfo.item_id)
      }
      break

    case 'setting':
      if (props.itemInfo) {
        await CreateItemModal({
          item_id: props.itemInfo.item_id
        })
      }
      break

    case 'ai':
      if (props.itemInfo) {
        await AiKnowledgeBaseModal(props.itemInfo.item_id)
        // AI 配置后需要刷新页面，确保配置（包括展开状态）立即生效
        window.location.reload()
      }
      break

    case 'openapi':
      if (props.itemInfo) {
        await OpenApiModal(props.itemInfo.item_id)
      }
      break

    case 'changelog':
      if (props.itemInfo) {
        await ChangeLogModal(props.itemInfo.item_id)
      }
      break

    case 'attorn':
      if (props.itemInfo) {
        await AttornModal(props.itemInfo.item_id)
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

.icon-item {
  width: 40px;
  height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0; // 防止收缩，保持正方形
  background-color: var(--color-bg-primary);
  border-radius: 8px;
  box-shadow: var(--shadow-xs);
  cursor: pointer;
  transition: all 0.15s ease;

  &:hover {
    background-color: var(--hover-overlay);
    box-shadow: var(--shadow-sm);
  }

  &.disabled {
    opacity: 0.5;
    cursor: not-allowed;

    &:hover {
      background-color: var(--color-bg-primary);
    }
  }

  i {
    color: var(--color-text-primary);
    font-size: 16px;
    transition: none; // 图标颜色不变化
  }

  // 图标颜色保持不变（克制设计 - hover 只改变背景）

  [data-theme="dark"] & {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    
    &:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }
  }
}

.icon-item a {
  color: var(--color-text-primary);
}

// 主题切换按钮特殊样式
.theme-toggle-item {
  i {
    color: var(--color-orange) !important;
  }

  &:hover i {
    color: var(--color-orange) !important;
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

