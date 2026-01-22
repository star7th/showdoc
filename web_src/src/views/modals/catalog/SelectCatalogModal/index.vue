<template>
  <CommonModal
    :show="show"
    :title="$t('page.select_catalog')"
    width="500px"
    @close="handleClose"
  >
    <div class="catalog-select-container">
      <!-- 目录下拉选择 -->
      <div class="form-item">
        <label class="form-label">{{ $t('page.select_catalog') }}</label>
        <CommonSelector
          v-model:value="selectedCatId"
          :options="catalogOptions"
          :placeholder="$t('page.select_catalog')"
          :show-search="true"
        />
      </div>
    </div>

    <template #footer>
      <CommonButton @click="handleNewCatalog">
        <i class="fas fa-plus"></i>
        {{ $t('catalog.new_catalog') }}
      </CommonButton>
      <div style="flex: 1;"></div>
      <CommonButton @click="handleClose">{{ $t('common.cancel') }}</CommonButton>
      <CommonButton theme="dark" @click="handleConfirm">{{ $t('common.confirm') }}</CommonButton>
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import request from '@/utils/request'
import Message from '@/components/Message'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonSelector from '@/components/CommonSelector.vue'
import CreateCatalogModal from '@/views/modals/catalog/CreateCatalogModal/index'

// Props
interface Props {
  itemId: number
  catId: number
  onClose: () => void
  onSelect: (catId: number) => void
}

const props = defineProps<Props>()

// Composables
const { t } = useI18n()

// Refs
const show = ref(false)
const catalogs = ref<any[]>([])
const selectedCatId = ref<number | string>(0)

// Computed
const catalogOptions = computed(() => {
  return catalogs.value.map(cat => ({
    label: cat.cat_name,
    value: cat.cat_id
  }))
})

// Methods

const handleClose = () => {
  props.onClose()
}

const handleConfirm = () => {
  props.onSelect(Number(selectedCatId.value))
}

const handleNewCatalog = async () => {
  console.log('handleNewCatalog 开始执行')
  const result = await CreateCatalogModal({
    itemId: props.itemId,
    parentCatId: Number(selectedCatId.value)
  })

  console.log('CreateCatalogModal 返回结果:', result)
  if (result.catId > 0) {
    console.log('开始刷新目录列表，新目录 ID:', result.catId)
    // 刷新目录列表并选中新创建的目录
    await loadCatalogs(result.catId)
    console.log('目录列表刷新完成')
    // 自动确认
    props.onSelect(result.catId)
    console.log('onSelect 调用完成')
  } else {
    console.log('result.catId <= 0，不执行刷新')
  }
}

const loadCatalogs = async (selectedCatalogId?: number) => {
  console.log('loadCatalogs 开始执行，selectedCatalogId:', selectedCatalogId)
  try {
    const data = await request('/api/catalog/catListName', {
      item_id: props.itemId
    }, 'post', false)

    console.log('API 返回数据:', data)
    if (data.error_code === 0 && data.data) {
      // 添加根目录选项（cat_id: '0', cat_name: '/'）
      const rootCatalog = { cat_id: '0', cat_name: '/' }
      // 确保 cat_id 是字符串类型，与 selectedCatId 一致
      catalogs.value = [rootCatalog, ...(data.data || []).map((cat: any) => ({
        ...cat,
        cat_id: String(Number(cat.cat_id || 0))  // 先转为数字再转字符串，确保格式统一
      }))]
      console.log('目录列表更新完成:', catalogs.value)
      // 设置当前选中的目录（转为字符串以匹配 a-select 的 value 类型）
      const targetId = String(selectedCatalogId !== undefined ? selectedCatalogId : props.catId)
      selectedCatId.value = targetId
      console.log('设置选中目录 ID:', targetId, '当前值:', selectedCatId.value)
    }
  } catch (error) {
    console.error('获取目录列表失败:', error)
    await AlertModal(t('common.operation_failed'))
  }
}

// Lifecycle
onMounted(() => {
  show.value = true
  loadCatalogs()
})
</script>

<style scoped lang="scss">
.catalog-select-container {
  padding: 20px 0;
}

.form-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-size: 14px;
  font-weight: 500;
  color: rgba(0, 0, 0, 0.85);
}
</style>

