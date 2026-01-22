<template>
  <CommonModal
    :show="show"
    :title="$t('catalog.select_catalog')"
    width="600px"
    @close="handleClose"
  >
    <div class="catalog-select">
      <a-tree
        v-model:selectedKeys="selectedKeys"
        :tree-data="treeData"
        :field-names="{ children: 'children', title: 'title', key: 'catId' }"
        :default-expand-all="true"
        @select="handleSelect"
      />
    </div>

    <template #footer>
      <CommonButton @click="handleClose">{{ $t('common.cancel') }}</CommonButton>
      <CommonButton type="primary" @click="handleConfirm">{{ $t('common.confirm') }}</CommonButton>
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import request from '@/utils/request'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'

// Props
interface Props {
  itemId: number
  currentCatId?: number
  onClose: () => void
  onSelect: (catId: number) => void
}

const props = withDefaults(defineProps<Props>(), {
  currentCatId: 0
})

// Composables
const { t } = useI18n()

// Refs
const show = ref(false)
const treeData = ref<any[]>([])
const selectedKeys = ref<number[]>([])

// Methods
const handleClose = () => {
  props.onClose()
}

const handleConfirm = () => {
  const catId = selectedKeys.value[0] !== undefined ? selectedKeys.value[0] : 0
  props.onSelect(catId)
}

const handleSelect = (keys: any) => {
  // 不做任何操作，等待用户点击确认
}

const loadCatalogs = async () => {
  try {
    const data = await request('/api/catalog/list', {
      item_id: props.itemId
    }, 'post', false)

    if (data.error_code === 0 && data.data) {
      // 添加根目录选项
      treeData.value = [
        {
          catId: 0,
          title: t('catalog.root_catalog'),
          children: transformCatalogData(data.data)
        }
      ]

      // 设置当前选中的目录
      if (props.currentCatId > 0) {
        selectedKeys.value = [props.currentCatId]
      } else {
        selectedKeys.value = [0]
      }
    }
  } catch (error) {
    console.error('获取目录列表失败:', error)
  }
}

const transformCatalogData = (list: any[]): any[] => {
  return list.map(item => ({
    catId: item.cat_id,
    title: item.cat_name || t('catalog.untitled_catalog'),
    children: item.catalogs ? transformCatalogData(item.catalogs) : []
  }))
}

// Lifecycle
onMounted(() => {
  show.value = true
  loadCatalogs()
})
</script>

<style scoped lang="scss">
.catalog-select {
  max-height: 400px;
  overflow-y: auto;

  :deep(.ant-tree) {
    background-color: transparent;
  }
}
</style>

