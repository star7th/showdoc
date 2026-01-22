<template>
  <div class="export-file-modal">
    <CommonModal
      :class="{ show }"
      :title="$t('item.export')"
      :icon="['fa', 'fa-download']"
      @close="handleClose"
    >
      <div class="modal-content">
        <!-- 导出格式选择 -->
        <CommonTab
          :items="formatItems"
          :value="exportFormat"
          type="segmented"
          @update-value="exportFormat = $event"
        >
          <!-- Word 格式选项 -->
          <template #word>
            <div class="form-section">
              <label class="form-label">{{ $t('item.export_range') }}</label>
              <CommonTab
                :items="rangeItems"
                :value="exportType"
                type="segmented"
                @update-value="exportType = $event"
              />

              <!-- 目录选择 -->
              <div v-if="exportType === 2" class="form-group">
                <label class="form-label">{{ $t('page.catalog') }}</label>
                <CommonSelector
                  v-model:value="catId"
                  :placeholder="$t('page.catalog')"
                  :show-search="true"
                  :options="catalogOptions"
                  @change="handleCatalogChange"
                />
              </div>

              <!-- 页面选择 -->
              <div v-if="exportType === 2 && pages.length > 0" class="form-group">
                <label class="form-label">{{ $t('page.page_info') }}</label>
                <CommonSelector
                  v-model:value="pageId"
                  :show-search="true"
                  :options="pageOptions"
                />
              </div>
            </div>
          </template>

          <!-- Markdown 格式提示 -->
          <template #markdown>
            <div class="tips">
              <p class="tips-text">{{ $t('item.export_markdown_tips') }}</p>
            </div>
          </template>

          <!-- HTML 格式提示 -->
          <template #html>
            <div class="tips">
              <p class="tips-text">{{ $t('item.export_html_tips') }}</p>
            </div>
          </template>
        </CommonTab>
      </div>
      <div class="modal-footer">
        <CommonButton
          :text="$t('common.cancel')"
          :theme="'light'"
          @click="handleClose"
        />
        <CommonButton
          :text="$t('common.confirm')"
          :theme="'dark'"
          :spinning="loading"
          @click="handleExport"
        />
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonTab, { TabItem } from '@/components/CommonTab.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonSelector from '@/components/CommonSelector.vue'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import request from '@/utils/request'
import { getUserInfoFromStorage } from '@/models/user'
import { getServerHost, appendUrlParams } from '@/utils/system'

const { t } = useI18n()

const props = defineProps<{
  onClose: (result: boolean) => void
  itemId: number
}>()

// 格式选项
const formatItems = computed<TabItem[]>(() => {
  const items: TabItem[] = [
    { text: t('item.export_format_word'), value: 'word' },
    { text: t('item.export_format_html'), value: 'html' }
  ]
  if (showMarkdown.value) {
    items.splice(1, 0, { text: t('item.export_format_markdown'), value: 'markdown' })
  }
  return items
})

// 导出范围选项
const rangeItems = computed<TabItem[]>(() => [
  { text: t('item.export_all'), value: 1 },
  { text: t('item.export_cat'), value: 2 }
])

const show = ref(false)
const loading = ref(false)
const exportFormat = ref<string | number>('word')
const exportType = ref<string | number>(1)
const catId = ref<string | number>('')
const pageId = ref<string | number>('0')
const showMarkdown = ref(true)
const catalogs = ref<any[]>([])
const pages = ref<any[]>([{ page_id: '0', page_title: t('item.all_pages') }])

// 获取用户信息
const userInfo = getUserInfoFromStorage()
const userToken = userInfo?.user_token || ''

// 计算目录列表（包含子目录）
const catalogOptions = computed(() => {
  const info = catalogs.value.slice(0)
  const catArray: any[] = []

  for (let i = 0; i < info.length; i++) {
    catArray.push(info[i])
    const sub = info[i]['sub']
    if (sub && sub.length > 0) {
      for (let j = 0; j < sub.length; j++) {
        catArray.push({
          cat_id: sub[j]['cat_id'],
          cat_name: info[i]['cat_name'] + ' / ' + sub[j]['cat_name']
        })

        const subSub = sub[j]['sub']
        if (subSub && subSub.length > 0) {
          for (let k = 0; k < subSub.length; k++) {
            catArray.push({
              cat_id: subSub[k]['cat_id'],
              cat_name:
                info[i]['cat_name'] +
                ' / ' +
                sub[j]['cat_name'] +
                ' / ' +
                subSub[k]['cat_name']
            })
          }
        }
      }
    }
  }

  catArray.unshift({ cat_id: '', cat_name: t('item.none') })
  return catArray.map(cat => ({
    label: cat.cat_name,
    value: cat.cat_id
  }))
})

// 计算页面选项列表
const pageOptions = computed(() => {
  return pages.value.map(page => ({
    label: page.page_title,
    value: page.page_id
  }))
})

// 获取目录列表
const getCatalog = async (itemId: number) => {
  try {
    const result = await request('/api/catalog/catListGroup', {
      item_id: itemId
    })
    if (result.error_code === 0 && result.data) {
      catalogs.value = result.data
    }
  } catch (error) {
    console.error('获取目录失败:', error)
  }
}

// 获取某目录下的所有页面
const getPages = async (catalogId: string) => {
  if (!catalogId) {
    pages.value = [{ page_id: '0', page_title: t('item.all_pages') }]
    pageId.value = '0'
    return
  }

  try {
    const result = await request('/api/catalog/getPagesBycat', {
      item_id: props.itemId,
      cat_id: catalogId
    })
    if (result.error_code === 0 && result.data) {
      pages.value = [
        { page_id: '0', page_title: t('item.all_pages') },
        ...result.data
      ]
      pageId.value = '0'
    }
  } catch (error) {
    console.error('获取页面失败:', error)
  }
}

// 目录变化时获取页面
const handleCatalogChange = (value: string) => {
  getPages(value)
}

// 检查 Markdown 导出限制
const checkMarkdownLimit = async () => {
  try {
    const result = await request('/api/export/checkMarkdownLimit', {
      export_format: exportFormat.value
    })
    return result.error_code === 0
  } catch (error) {
    console.error('检查Markdown限制失败:', error)
    return false
  }
}

// 执行导出
const handleExport = async () => {
  if (loading.value) return

  // 检查 Markdown 导出限制
  const canExport = await checkMarkdownLimit()
  if (!canExport) {
    await AlertModal(t('common.op_failed'))
    return
  }

  const host = getServerHost()
  let url = ''

  if (exportFormat.value === 'word') {
    const catIdParam = exportType.value === 1 ? '' : catId.value
    url = appendUrlParams(`${host}/api/export/word`, {
      item_id: props.itemId,
      cat_id: catIdParam,
      page_id: pageId.value,
      user_token: userToken
    })
  } else if (exportFormat.value === 'markdown') {
    url = appendUrlParams(`${host}/api/export/markdown`, {
      item_id: props.itemId,
      user_token: userToken
    })
  } else if (exportFormat.value === 'html') {
    url = appendUrlParams(`${host}/api/exportHtml/export`, {
      item_id: props.itemId,
      user_token: userToken
    })
  }

  // 触发下载
  window.location.href = url
  Message.success(t('common.export_success'))
  handleClose(true)
}

const handleClose = (result: boolean = false) => {
  show.value = false
  setTimeout(() => {
    props.onClose(result)
  }, 300)
}

onMounted(async () => {
  // 获取目录列表
  await getCatalog(props.itemId)

  // 获取项目类型，如果是 runapi 项目则不显示 markdown 选项
  try {
    const result = await request('/api/item/info', {
      item_id: props.itemId
    })
    if (result.error_code === 0 && result.data) {
      if (result.data.item_type === '3') {
        showMarkdown.value = false // 不显示 markdown 选项
      }
    }
  } catch (error) {
    console.error('获取项目信息失败:', error)
  }

  setTimeout(() => {
    show.value = true
  })
})
</script>

<style scoped lang="scss">
.modal-content {
  width: 520px;
  padding: 30px 40px;
  border-bottom: 1px solid var(--color-interval);
}

.form-section {
  margin-bottom: 24px;

  &:last-child {
    margin-bottom: 0;
  }
}

.form-group {
  margin-top: 16px;
}

.form-label {
  display: block;
  margin-bottom: 10px;
  color: var(--color-text-primary);
  font-size: var(--font-size-m);
  font-weight: 500;
}

.tips {
  margin-top: 20px;
  padding: 12px 16px;
  background-color: var(--color-bg-secondary);
  border-radius: 6px;
  border-left: 3px solid var(--color-primary);

  .tips-text {
    color: var(--color-text-secondary);
    font-size: var(--font-size-s);
    line-height: 1.6;
    margin: 0;
  }
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 15px;
  height: 90px;

  .secondary-button,
  .primary-button {
    width: 160px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: var(--font-size-m);
    font-weight: bold;
    cursor: pointer;
    white-space: nowrap;
  }

  .secondary-button {
    background-color: var(--color-obvious);
    color: var(--color-primary);

    &:hover {
      background-color: var(--color-secondary);
    }
  }

  .primary-button {
    background-color: var(--color-primary);
    color: white;

    &:hover {
      opacity: 0.9;
    }
  }
}

// 暗黑主题适配
[data-theme='dark'] .tips {
  background-color: var(--color-bg-secondary);

  .tips-text {
    color: var(--color-text-secondary);
  }
}

[data-theme='dark'] .form-label {
  color: var(--color-text-primary);
}
</style>

