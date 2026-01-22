<template>
  <CommonModal
    :show="show"
    :title="$t('catalog.new_catalog')"
    width="500px"
    @close="handleClose"
  >
    <div class="create-catalog-container">
      <div class="form-item">
        <label class="form-label">{{ $t('catalog.cat_name') }} <span class="required">*</span></label>
        <CommonInput
          v-model="form.catName"
          :placeholder="$t('catalog.cat_name_placeholder')"
          allow-clear
        />
      </div>

      <div class="form-item">
        <label class="form-label">{{ $t('catalog.parent_catalog') }}</label>
        <a-select
          v-model:value="form.parentCatId"
          :options="catalogOptions"
          :placeholder="$t('catalog.select_parent_catalog')"
          show-search
          :filter-option="filterOption"
          allow-clear
          style="width: 100%;"
        />
      </div>
    </div>

    <template #footer>
      <CommonButton @click="handleClose">{{ $t('common.cancel') }}</CommonButton>
      <CommonButton theme="dark" @click="handleConfirm">{{ $t('common.confirm') }}</CommonButton>
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonInput from '@/components/CommonInput.vue'
import request from '@/utils/request'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

interface Props {
  itemId: number
  parentCatId?: number
  onClose?: () => void
  onSuccess?: (catId: number, catName: string) => void
}

const props = defineProps<Props>()

const show = ref(true)
const form = reactive({
  catName: '',
  parentCatId: Number(props.parentCatId || 0)  // 确保是数字类型
})

const catalogList = ref<any[]>([])

// Computed
const catalogOptions = computed(() => {
  const options = [
    { label: t('catalog.root_catalog'), value: 0 }
  ]
  catalogList.value.forEach(cat => {
    options.push({
      label: cat.cat_name,
      value: Number(cat.cat_id || 0)  // 统一转为数字类型
    })
  })
  return options
})

// Methods
const filterOption = (input: string, option: any) => {
  return option.label.toLowerCase().includes(input.toLowerCase())
}

const handleClose = () => {
  show.value = false
  props.onClose?.()
}

const loadCatalogList = async () => {
  try {
    const result = await request('/api/catalog/catListName', {
      item_id: props.itemId
    }, 'post', false)
    if (result.error_code === 0) {
      // 统一将 cat_id 转为数字类型，保持数据一致性
      catalogList.value = (result.data || []).map((cat: any) => ({
        ...cat,
        cat_id: Number(cat.cat_id || 0)
      }))
    }
  } catch (error) {
    console.error('获取目录列表失败:', error)
  }
}

const handleConfirm = async () => {
  if (!form.catName.trim()) {
    message.error(t('catalog.cat_name_required'))
    return
  }

  try {
    const result = await request('/api/catalog/save', {
      item_id: props.itemId,
      cat_id: 0,
      parent_cat_id: form.parentCatId,
      cat_name: form.catName.trim()
    }, 'post', false)

    if (result.error_code === 0) {
      message.success(t('common.save_success'))
      show.value = false
      // 兼容两种返回格式：
      // 1. 旧版格式：{"error_code": 0, "data": 3397743}（data 是整数 cat_id）
      // 2. 标准格式：{"error_code": 0, "data": {"cat_id": 123}}（data 是对象）
      let newCatId = 0
      if (typeof result.data === 'number') {
        // 旧版格式：data 直接就是 cat_id
        newCatId = result.data
      } else if (result.data && result.data.cat_id) {
        // 标准格式：data 是对象，包含 cat_id 字段
        newCatId = Number(result.data.cat_id)
      }
      newCatId = Number(newCatId || 0)
      console.log('创建目录成功，新目录 ID:', newCatId, '返回数据:', result)
      props.onSuccess?.(newCatId, form.catName.trim())
    } else {
      message.error(result.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('创建目录失败:', error)
    message.error(t('common.op_failed'))
  }
}

onMounted(() => {
  loadCatalogList()
})
</script>

<style scoped lang="scss">
.create-catalog-container {
  padding: 20px 0;

  .form-item {
    margin-bottom: 20px;

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--color-text-1);

      .required {
        color: var(--color-danger-6);
        margin-left: 4px;
      }
    }
  }
}
</style>

