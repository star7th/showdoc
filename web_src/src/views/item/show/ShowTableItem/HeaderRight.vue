<template>
  <div class="header-right float-right">
    <!-- 分享 -->
    <div class="icon-item" @click="handleShare">
      <a-tooltip :title="$t('item.share')" placement="bottom">
        <i class="fa-regular fa-share-nodes"></i>
      </a-tooltip>
    </div>

    <!-- 保存 -->
    <div
      v-if="itemInfo.item_manage == 1"
      class="icon-item"
      @click="$emit('save')"
    >
      <a-tooltip :title="$t('common.save')" placement="bottom">
        <i class="fa-regular fa-save"></i>
      </a-tooltip>
    </div>

    <!-- 导出 -->
    <div
      v-if="itemInfo.item_edit == 1"
      class="icon-item"
      @click="$emit('export')"
    >
      <a-tooltip :title="$t('item.export')" placement="bottom">
        <i class="fa-regular fa-arrow-down-to-bracket"></i>
      </a-tooltip>
    </div>

    <!-- 导入 -->
    <div
      v-if="itemInfo.item_manage == 1"
      class="icon-item"
      @click="handleImport"
    >
      <a-tooltip :title="$t('item.import')" placement="bottom">
        <i class="fa-regular fa-arrow-up-from-bracket"></i>
      </a-tooltip>
    </div>

    <!-- 成员管理 -->
    <div
      v-if="itemInfo.item_manage == 1"
      class="icon-item"
      @click="handleMember"
    >
      <a-tooltip :title="$t('item.memberManage')" placement="bottom">
        <i class="fa-regular fa-users"></i>
      </a-tooltip>
    </div>

    <!-- 登录 -->
    <div v-if="itemInfo.is_login != 1" class="icon-item" @click="handleLogin">
      <a-tooltip :title="$t('user.login')" placement="bottom">
        <i class="fa-regular fa-user"></i>
      </a-tooltip>
    </div>

    <!-- 关于ShowDoc -->
    <div v-if="itemInfo.is_login != 1" class="icon-item" @click="openHelp">
      <a-tooltip :title="$t('item.aboutShowdoc')" placement="bottom">
        <i class="fa-regular fa-circle-info"></i>
      </a-tooltip>
    </div>

    <!-- 更多菜单 -->
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

    <!-- 导入弹窗 -->
    <CommonModal
      v-if="showImport"
      :show="showImport"
      :title="$t('item.importExcel')"
      @close="showImport = false"
    >
      <div class="text-center">
        <input
          type="file"
          name="xlfile"
          id="xlf"
          accept=".xlsx,.xls"
          @change="handleImportFile"
        />
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
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
const emit = defineEmits<{
  (e: 'save'): void
  (e: 'export'): void
  (e: 'import', file: File): void
  (e: 'reload'): void
}>()

const { t } = useI18n()
const router = useRouter()

// 弹窗显示状态
const showImport = ref(false)

// 下拉菜单项
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

// 事件处理
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

const openHelp = () => {
  window.open('https://www.showdoc.com.cn/help', '_blank')
}

const handleImport = () => {
  showImport.value = true
}

const handleImportFile = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files[0]) {
    emit('import', target.files[0])
    showImport.value = false
  }
}

const handleDropdownSelect = async (item: any) => {
  const key = item.value
  switch (key) {
    case 'update':
      if (props.itemInfo) {
        await CreateItemModal({
          item_id: props.itemInfo.item_id,
        })
      }
      break
    case 'attorn':
      if (props.itemInfo) {
        const result = await AttornModal(props.itemInfo.item_id)
        if (result) {
          handleGoback()
        }
      }
      break
    case 'changelog':
      if (props.itemInfo) {
        await ChangeLogModal(props.itemInfo.item_id)
      }
      break
    case 'archive':
      if (props.itemInfo) {
        const result = await ArchiveModal(props.itemInfo.item_id)
        if (result) {
          handleGoback()
        }
      }
      break
    case 'delete':
      if (props.itemInfo) {
        const result = await DeleteModal(props.itemInfo.item_id)
        if (result) {
          handleGoback()
        }
      }
      break
  }
}

const handleGoback = () => {
  router.push('/item/index')
  // 由于x_spreadsheet的固有缺陷，只能重新刷新销毁实例了
  setTimeout(() => {
    window.location.reload()
  }, 200)
}
</script>

<style scoped lang="scss">
.header-right {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 24px;
  margin-right: 20px;

  > .inline {
    display: inline-flex;
  }
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
  flex-shrink: 0; // 防止收缩，保持正方形

  &:hover {
    background-color: var(--hover-overlay);
    box-shadow: var(--shadow-sm);
  }

  i {
    font-size: 16px;
  }
}

.text-center {
  text-align: center;
}
</style>
