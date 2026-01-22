<template>
  <div class="share-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('item.share')"
      @close="handleClose"
    >
      <div class="modal-content">
        <!-- 页面/项目地址 -->
        <p class="label">{{ pageId ? $t('item.item_page_address') : $t('item.item_address') }}:</p>
        <div class="link-container">
          <code class="link">{{ sharePageLink }}</code>
          <i class="fas fa-copy copy-icon" @click="handleCopyPage"></i>
        </div>

        <!-- 单页链接设置（仅选中页面时显示） -->
        <div v-if="pageId && props.item_info?.item_edit" class="single-page-section">
          <p class="label">
            <CommonSwitch v-model="isCreateSinglePage" :label="$t('item.create_single_page')" @change="handleSinglePageChange" />
          </p>

          <template v-if="isCreateSinglePage">
            <div class="link-container single-link">
              <code class="link">{{ shareSingleLink }}</code>
              <i class="fas fa-copy copy-icon" @click="handleCopySingle"></i>
            </div>

            <p class="label">{{ $t('item.expire_time') }}:</p>
            <a-radio-group v-model:value="expireDays" @change="handleExpireChange">
              <a-radio :value="0">{{ $t('item.permanent') }}</a-radio>
              <a-radio :value="1">{{ $t('item.one_day') }}</a-radio>
              <a-radio :value="7">{{ $t('item.seven_days') }}</a-radio>
              <a-radio :value="30">{{ $t('item.one_month') }}</a-radio>
              <a-radio :value="180">{{ $t('item.half_year') }}</a-radio>
            </a-radio-group>
          </template>
        </div>

        <div v-if="pageId && (isCreateSinglePage || pageUniqueKey)" class="tips-text">
          {{ $t('item.create_single_page_tips') }}
        </div>
      </div>

      <div class="modal-footer">
        <div class="secondary-button" @click="handleClose">{{ $t('common.close') }}</div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import CommonModal from '@/components/CommonModal.vue'
import CommonSwitch from '@/components/CommonSwitch.vue'
import ConfirmModal from '@/components/ConfirmModal'
import AlertModal from '@/components/AlertModal'
import { copyToClipboard } from '@/utils/tools'
import Message from '@/components/Message'
import request from '@/utils/request'

const { t } = useI18n()
const router = useRouter()

const props = defineProps<{
  item_domain?: string
  item_id?: number
  page_id?: number
  page_unique_key?: string
  page_title?: string
  item_info?: {
    item_id?: number
    item_domain?: string
    item_edit?: boolean
    unique_key?: string
    page_title?: string
  }
  onClose: (result: boolean) => void
}>()

// 传递给模板的响应式引用
const pageId = computed(() => props.page_id)
const itemInfo = computed(() => props.item_info)

const show = ref(false)
const isCreateSinglePage = ref(false)
const expireDays = ref(0)
const pageUniqueKey = ref(props.page_unique_key || '')

// 生成项目/页面链接
const sharePageLink = computed(() => {
  const itemId = props.item_id || itemInfo.value?.item_id
  if (!itemId) return ''

  const domain = props.item_domain || itemInfo.value?.item_domain || itemId

  // 获取当前的基础 URL（协议 + 域名 + 路径，如 https://example.com/web/）
  const baseUrl = window.location.origin + window.location.pathname

  // 如果有 page_id，则链接到具体页面
  if (pageId.value && pageId.value > 0) {
    return `${baseUrl}#/${domain}/${pageId.value}`
  }

  // 默认链接到项目首页
  return `${baseUrl}#/${domain}`
})

// 生成单页链接
const shareSingleLink = computed(() => {
  if (!pageUniqueKey.value) return ''

  // 获取当前的基础 URL（协议 + 域名 + 路径，如 https://example.com/web/）
  const baseUrl = window.location.origin + window.location.pathname
  return `${baseUrl}#/p/${pageUniqueKey.value}`
})

// 复制页面链接
const handleCopyPage = async () => {
  const success = await copyToClipboard(sharePageLink.value)
  if (success) {
    Message.success(t('common.copy_success'))
  } else {
    AlertModal(t('common.error'))
  }
}

// 复制单页链接
const handleCopySingle = async () => {
  const success = await copyToClipboard(shareSingleLink.value)
  if (success) {
    Message.success(t('common.copy_success'))
  } else {
    AlertModal(t('common.error'))
  }
}

// 单页链接设置变化
const handleSinglePageChange = async (checked: boolean) => {
  if (checked) {
    // 用户勾选了，需要创建单页
    await createSinglePage()
  } else {
    // 用户取消了勾选，需要先确认
    const confirmed = await ConfirmModal({
      msg: t('item.cancel_single'),
      confirmText: t('item.cancel_single_yes'),
      cancelText: t('item.cancel_single_no')
    })

    if (confirmed) {
      // 用户确认取消，调用取消API
      isCreateSinglePage.value = false
      const result = await request('/api/page/createSinglePage', {
        page_id: props.page_id,
        isCreateSiglePage: 'false',
        expire_days: 0
      }, 'post', false)

      if (result.error_code === 0) {
        pageUniqueKey.value = ''
        Message.success(t('common.save_success'))
      } else {
        await AlertModal(result.error_message || t('common.op_failed'))
        // 失败时，恢复勾选状态
        isCreateSinglePage.value = true
      }
    } else {
      // 用户取消操作，恢复勾选状态
      isCreateSinglePage.value = true
    }
  }
}

// 创建单页
const createSinglePage = async () => {
  if (!props.page_id || props.page_id <= 0) return

  try {
    const result = await request('/api/page/createSinglePage', {
      page_id: props.page_id,
      isCreateSiglePage: 'true',  // 创建单页，固定传 true
      expire_days: expireDays.value
    }, 'post', false)

    if (result.error_code === 0 && result.data) {
      const uniqueKey = result.data.unique_key
      if (uniqueKey) {
        pageUniqueKey.value = uniqueKey
        // 不设置 isCreateSinglePage，由用户操作（勾选框）控制
      }
      Message.success(t('common.save_success'))
    } else {
      await AlertModal(result.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('创建单页失败:', error)
    await AlertModal(t('common.op_failed'))
  }
}

// 有效期变化
const handleExpireChange = async () => {
  // expireDays 已经通过 v-model:value 自动更新，无需手动设置
  // 只有在勾选状态且存在单页链接时才更新有效期
  // 直接调用 API，不调用 createSinglePage 以免重复生成链接
  if (isCreateSinglePage.value && pageUniqueKey.value) {
  try {
    const result = await request('/api/page/createSinglePage', {
      page_id: props.page_id,
        isCreateSiglePage: 'true',
        expire_days: expireDays.value
    }, 'post', false)

    if (result.error_code === 0 && result.data) {
      // 如果后端返回了新的 unique_key，则更新前端显示的链接
      const uniqueKey = result.data.unique_key
      if (uniqueKey) {
        pageUniqueKey.value = uniqueKey
      }
      Message.success(t('common.save_success'))
    } else {
      await AlertModal(result.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('更新单页有效期失败:', error)
    await AlertModal(t('common.op_failed'))
    }
  }
}

// 关闭弹窗
const handleClose = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(false)
  }, 300)
}

onMounted(() => {
  // 如果传入了 page_unique_key，说明已经有单页链接
  if (props.page_unique_key) {
    isCreateSinglePage.value = true
    pageUniqueKey.value = props.page_unique_key
    // 获取单页信息以确定有效期
    fetchSinglePageInfo()
  } else if (props.page_id && props.page_id > 0) {
    // 如果有 page_id 但没有 unique_key，检查该页面是否已有单页
    checkSinglePageExists()
  }

  setTimeout(() => {
    show.value = true
  })
})

// 获取单页信息
const fetchSinglePageInfo = async () => {
  if (!props.page_id) return

  const uniqueKey = props.page_unique_key || pageUniqueKey.value

  try {
    const result = await request('/api/page/infoByKey', {
      unique_key: uniqueKey || ''
    }, 'post', false)

    if (result.error_code === 0 && result.data) {
      // 只更新有效期，不要覆盖 pageUniqueKey

      console.log('[fetchSinglePageInfo] API 返回数据:', result.data)
      console.log('[fetchSinglePageInfo] expire_time:', result.data.expire_time)

      // 根据过期时间计算天数
      const now = Math.floor(Date.now() / 1000)
      const expireTime = result.data.expire_time

      // 如果 expire_time 为 0 或负数，表示永久有效
      if (!expireTime || expireTime <= 0) {
        expireDays.value = 0
        console.log('[fetchSinglePageInfo] 永久有效，设置 expireDays.value: 0')
      } else {
        // 计算剩余天数
        const diffDays = Math.round((expireTime - now) / (24 * 60 * 60))
        console.log('[fetchSinglePageInfo] 当前时间:', now, 'diffDays:', diffDays)

      if (diffDays <= 1) {
        expireDays.value = 1
      } else if (diffDays <= 7) {
        expireDays.value = 7
      } else if (diffDays <= 30) {
        expireDays.value = 30
      } else if (diffDays <= 180) {
        expireDays.value = 180
      } else {
        expireDays.value = 0
      }
        console.log('[fetchSinglePageInfo] 设置 expireDays.value:', expireDays.value)
      }
    } else {
      // API 返回错误，清空 pageUniqueKey
      pageUniqueKey.value = ''
      isCreateSinglePage.value = false
    }
  } catch (error) {
    console.error('获取单页信息失败:', error)
    // 请求失败时，也清空 pageUniqueKey
    pageUniqueKey.value = ''
    isCreateSinglePage.value = false
  }
}

// 检查页面是否已有单页
const checkSinglePageExists = async () => {
  if (!props.page_id || props.page_id <= 0) return

  try {
    const result = await request('/api/page/info', {
      page_id: props.page_id,
    }, 'post', false)

    if (result.error_code === 0 && result.data) {
      // 检查是否有 unique_key（表示已创建单页）
      if (result.data.unique_key) {
        pageUniqueKey.value = result.data.unique_key
        isCreateSinglePage.value = true
        // 获取单页的有效期信息
        fetchSinglePageInfo()
      } else {
        pageUniqueKey.value = ''
        isCreateSinglePage.value = false
      }
    }
  } catch (error) {
    console.error('检查单页失败:', error)
  }
}
</script>

<style scoped lang="scss">
.modal-content {
  width: 500px;
  padding: 30px 40px;
  border-bottom: 1px solid var(--color-interval);
}

.label {
  margin-bottom: 12px;
  font-size: 14px;
  color: var(--color-text-primary);
  font-weight: 500;
}

.link-container {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: var(--color-bg-secondary);
  border-radius: 4px;
  border: 1px solid var(--color-border);

  &.single-link {
    background: var(--color-info-bg);
  }
}

.link {
  flex: 1;
  padding: 8px 12px;
  font-size: 13px;
  font-family: 'Courier New', monospace;
  color: var(--color-text-primary);
  background: var(--color-bg-primary);
  border: 1px solid var(--color-border);
  border-radius: 4px;
  word-break: break-all;
  overflow-x: auto;
}

.copy-icon {
  flex-shrink: 0;
  cursor: pointer;
  color: var(--color-text-secondary);
  transition: color 0.15s ease;
  font-size: 14px;

  &:hover {
    color: var(--color-active);
  }
}

.single-page-section {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px dashed var(--color-border);
}

.tips-text {
  margin-top: 15px;
  padding: 10px;
  font-size: 12px;
  color: var(--color-text-secondary);
  background: rgba(0, 0, 0, 0.03);
  border-radius: 4px;
  line-height: 1.6;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;

  .secondary-button,
  .primary-button {
    width: 160px;
    margin: 0 7.5px;
  }
}
</style>

