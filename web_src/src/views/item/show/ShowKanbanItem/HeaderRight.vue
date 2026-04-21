<template>
  <div class="header-right float-right">
    <div class="icon-item" @click="handleShare">
      <a-tooltip :title="$t('item.share')" placement="bottom">
        <i class="fa-regular fa-share-nodes"></i>
      </a-tooltip>
    </div>

    <div
      v-if="itemInfo.item_manage == 1"
      class="icon-item"
      @click="handleMember"
    >
      <a-tooltip :title="$t('item.memberManage')" placement="bottom">
        <i class="fa-regular fa-users"></i>
      </a-tooltip>
    </div>

    <div v-if="itemInfo.is_login != 1" class="icon-item" @click="handleLogin">
      <a-tooltip :title="$t('user.login')" placement="bottom">
        <i class="fa-regular fa-user"></i>
      </a-tooltip>
    </div>

    <CommonDropdownMenu
      v-if="itemInfo.item_manage == 1"
      :list="menuItems"
      trigger="hover"
      placement="bottom"
      :offsetX="-80"
      @select="handleDropdownSelect"
    >
      <div class="icon-item">
        <i class="fa-regular fa-ellipsis"></i>
      </div>
    </CommonDropdownMenu>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import CommonDropdownMenu from '@/components/CommonDropdownMenu.vue'
import ShareModal from '@/views/modals/item/ShareModal/index'
import MemberModal from '@/views/modals/item/MemberModal/index'
import ArchiveModal from '@/views/modals/item/ArchiveModal/index'
import AttornModal from '@/views/modals/item/AttornModal/index'
import DeleteModal from '@/views/modals/item/DeleteModal/index'
import CreateItemModal from '@/views/modals/item/CreateItemModal'
import ChangeLogModal from '@/views/modals/item/ChangeLogModal/index'

interface Props {
  itemInfo: any
}

const props = defineProps<Props>()
const emit = defineEmits<{ (e: 'reload'): void }>()

const { t } = useI18n()
const router = useRouter()

const menuItems = computed(() => [
  {
    value: 'update',
    icon: ['fa-regular', 'fa-edit'],
    text: t('item.updateBaseInfo'),
  },
  {
    value: 'attorn',
    icon: ['fa-regular', 'fa-recycle'],
    text: t('item.attornItem'),
  },
  {
    value: 'changelog',
    icon: ['fa-regular', 'fa-clock-rotate-left'],
    text: t('item.change_log'),
  },
  {
    value: 'archive',
    icon: ['fa-regular', 'fa-box-archive'],
    text: t('item.archiveItem'),
  },
  {
    value: 'delete',
    icon: ['fa-regular', 'fa-trash-can'],
    text: t('item.deleteItem'),
    danger: true,
  },
])

const handleShare = async () => {
  if (props.itemInfo) {
    await ShareModal({
      item_domain: props.itemInfo.item_domain,
      item_id: props.itemInfo.item_id,
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

const handleGoback = () => {
  router.push('/item/index')
  setTimeout(() => { window.location.reload() }, 200)
}

const handleDropdownSelect = async (item: any) => {
  switch (item.value) {
    case 'update':
      if (props.itemInfo) await CreateItemModal({ item_id: props.itemInfo.item_id })
      break
    case 'attorn':
      if (props.itemInfo) {
        const r = await AttornModal(props.itemInfo.item_id)
        if (r) handleGoback()
      }
      break
    case 'changelog':
      if (props.itemInfo) await ChangeLogModal(props.itemInfo.item_id)
      break
    case 'archive':
      if (props.itemInfo) {
        const r = await ArchiveModal(props.itemInfo.item_id)
        if (r) handleGoback()
      }
      break
    case 'delete':
      if (props.itemInfo) {
        const r = await DeleteModal(props.itemInfo.item_id)
        if (r) handleGoback()
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
  margin-top: 24px;
  margin-right: 20px;
}

.icon-item {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--color-bg-primary);
  border-radius: 8px;
  box-shadow: var(--shadow-xs);
  cursor: pointer;
  transition: all 0.15s ease;
  font-size: 13px;
  flex-shrink: 0;

  &:hover {
    background-color: var(--hover-overlay);
    box-shadow: var(--shadow-sm);
  }

  i {
    font-size: 16px;
  }
}
</style>
