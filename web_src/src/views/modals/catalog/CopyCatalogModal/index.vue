<template>
  <CommonModal :show="show" :title="t('catalog.clone_move')" width="600px" @close="handleClose">
    <a-spin :spinning="loading">
      <a-form layout="vertical">
        <a-form-item :label="t('catalog.copy_or_move')">
          <CommonSelector
            v-model:value="isMove"
            :options="copyMoveOptions"
            @change="handleSelectItem"
          />
        </a-form-item>

        <a-form-item :label="t('catalog.target_project')">
          <CommonSelector
            v-model:value="toItemId"
            :options="itemListOptions"
            :show-search="true"
            @change="handleSelectItem"
          />
        </a-form-item>

        <a-form-item :label="t('catalog.target_catalog')">
          <CommonSelector
            v-model:value="newCatId"
            :options="catalogOptions"
          />
        </a-form-item>
      </a-form>
    </a-spin>

    <template #footer>
      <CommonButton
        theme="light"
        :text="t('common.cancel')"
        :disabled="loading"
        style="margin-right: 12px"
        @click="handleClose"
      />
      <CommonButton
        theme="dark"
        :text="t('common.confirm')"
        :disabled="loading"
        @click="handleConfirm"
      />
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonSelector from '@/components/CommonSelector.vue'
import request from '@/utils/request'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'

// Props
interface Props {
  catId?: number
  itemId?: number
}

const props = withDefaults(defineProps<Props>(), {
  catId: 0,
  itemId: 0
})

// Emits
const emit = defineEmits<{
  close: []
}>()

// Composables
const { t } = useI18n()

// Refs
const show = ref(true)
const loading = ref(false)
const isMove = ref(false)
const toItemId = ref(props.itemId)
const newCatId = ref(0)
const itemList = ref<any[]>([])
const catalogs = ref<any[]>([{ cat_id: 0, cat_name: '/' }])

const copyMoveOptions = computed(() => [
  { label: t('catalog.copy_to'), value: false },
  { label: t('catalog.move_to'), value: true }
])

const itemListOptions = computed(() => {
  return itemList.value.map(item => ({
    label: item.item_name,
    value: item.item_id
  }))
})

const catalogOptions = computed(() => {
  return catalogs.value.map(cat => ({
    label: cat.cat_name,
    value: cat.cat_id
  }))
})

// Methods
const getItemList = async () => {
  try {
    const result = await request('/api/item/myList', {}, 'post')

    if (result.error_code === 0) {
      itemList.value = result.data || []
      toItemId.value = props.itemId
    }
  } catch (error) {
    console.error('获取项目列表失败:', error)
  }
}

const getCatalog = async (itemId: number) => {
  try {
    const result = await request('/api/catalog/catListName', {
      item_id: itemId
    }, 'post')

    if (result.error_code === 0) {
      newCatId.value = 0
      const catalogList = (result.data || []).map((cat: any) => ({
        ...cat,
        cat_id: Number(cat.cat_id || 0)
      }))
      catalogs.value = [{ cat_id: 0, cat_name: '/' }, ...catalogList]
    }
  } catch (error) {
    console.error('获取目录列表失败:', error)
  }
}

const handleSelectItem = (itemId: number) => {
  getCatalog(itemId)
}

const handleConfirm = async () => {
  // 不能将目录移动/克隆到自身
  if (newCatId.value === props.catId) {
    await AlertModal('不能将目录移动/克隆到自身')
    return
  }

  loading.value = true
  try {
    const result = await request('/api/catalog/copy', {
      cat_id: props.catId,
      new_p_cat_id: newCatId.value,
      to_item_id: toItemId.value,
      is_del: isMove.value ? 1 : 0
    }, 'post')

    if (result.error_code === 0) {
      Message.success(t('common.op_success'))
      handleClose()
    } else {
      await AlertModal(result.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('复制/移动目录失败:', error)
    await AlertModal(t('common.op_failed'))
  } finally {
    loading.value = false
  }
}

const handleClose = () => {
  emit('close')
}

// Lifecycle
onMounted(() => {
  getItemList()
  getCatalog(props.itemId)
})
</script>

<style scoped lang="scss">
:deep(.ant-form-item) {
  margin-bottom: 16px;
}
</style>

