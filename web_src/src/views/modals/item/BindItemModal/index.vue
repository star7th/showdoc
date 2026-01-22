<template>
  <div class="bind-item-modal">
    <CommonModal
      :class="{ show }"
      :title="t('item.item')"
      :icon="['fas', 'folder-plus']"
      width="500px"
      @close="handleClose"
    >
      <div class="form-content">
        <div class="form-item">
          <div class="form-label">{{ t('item.please_choose') }}</div>
          <a-select
            v-model:value="bindForm.item_id"
            mode="multiple"
            :placeholder="t('item.please_choose')"
            :options="myItemListOptions"
            :filter-option="filterOption"
            show-search
            style="width: 100%"
          >
          </a-select>
        </div>
        <div class="form-item">
          <a :href="itemIndexHref" target="_blank" class="link-text">
            {{ t('item.go_to_new_an_item') }}
          </a>
        </div>
      </div>
      <div class="modal-footer">
        <div class="secondary-button" @click="handleClose()">{{ t('common.cancel') }}</div>
        <div class="primary-button" @click="handleSubmit()">{{ t('common.confirm') }}</div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import CommonModal from '@/components/CommonModal.vue'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import { saveTeamItem } from '@/models/team'
import { getMyList } from '@/models/item'

const { t } = useI18n()
const router = useRouter()

const props = defineProps<{
  team_id: number
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const myItemList = ref<any[]>([])

const bindForm = ref({
  item_id: [] as number[]
})

// 转换项目列表格式
const myItemListOptions = computed(() => {
  return myItemList.value.map((item: any) => ({
    label: item.item_name,
    value: item.item_id
  }))
})

// 生成项目列表首页的路由链接
const itemIndexHref = computed(() => router.resolve({ path: '/item/index' }).href)

// 过滤选项
const filterOption = (input: string, option: any) => {
  return option.label.toLowerCase().includes(input.toLowerCase())
}

// 获取我的项目列表
const fetchMyItemList = async () => {
  try {
    const res = await getMyList(0)
    if (res.error_code === 0) {
      myItemList.value = (res.data || []).filter((item: any) => item.manage)
    }
  } catch (error) {
    console.error('获取项目列表失败:', error)
  }
}

// 提交绑定项目
const handleSubmit = async () => {
  if (!bindForm.value.item_id || bindForm.value.item_id.length === 0) {
    await AlertModal(t('item.item') + t('common.required'))
    return
  }

  try {
    // 批量绑定
    for (const itemId of bindForm.value.item_id) {
      await saveTeamItem(String(itemId), String(props.team_id))
    }
    Message.success(t('common.op_success'))
    handleClose()
    props.onClose(true)
  } catch (error) {
    console.error('绑定项目失败:', error)
    await AlertModal(t('common.op_failed'))
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
  fetchMyItemList()
  setTimeout(() => {
    show.value = true
  })
})
</script>

<style scoped lang="scss">
.form-content {
  padding: 20px 24px;
}

.form-item {
  margin-bottom: 24px;

  &:last-child {
    margin-bottom: 0;
  }
}

.form-label {
  font-size: 14px;
  color: var(--color-text-primary);
  margin-bottom: 10px;
  font-weight: 500;
}

.link-text {
  color: var(--color-active);
  text-decoration: none;
  font-size: 14px;

  &:hover {
    color: var(--color-active);
    text-decoration: underline;
  }
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;
  border-bottom: none;
  width: 100%;
}

.secondary-button,
.primary-button {
  width: 120px;
  margin: 0 8px;
  flex-shrink: 0;
}
</style>

