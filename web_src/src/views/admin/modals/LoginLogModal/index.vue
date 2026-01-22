<template>
  <CommonModal :show="show" :title="$t('admin.login_log')" :show-footer="false" width="70%" top="5vh" @close="handleClose">
    <CommonTable
      :table-header="loginLogHeader"
      :table-data="loginLogList"
      :pagination="false"
      row-key="id"
      max-height="500px"
    >
      <template #cell-user_agent="{ row }">
        <div class="truncate-text" :title="row.user_agent">
          {{ row.user_agent }}
        </div>
      </template>
    </CommonTable>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonTable from '@/components/CommonTable.vue'
import { getUserLoginLog } from '@/models/admin'

const { t } = useI18n()

const props = defineProps<{
  uid: number
  onClose: () => void
}>()

const show = ref(false)
const loginLogList = ref<any[]>([])

const loginLogHeader = [
  { title: t('admin.token_create_time'), key: 'addtime', width: 160 },
  { title: t('admin.token_update_time'), key: 'last_check_time', width: 160 },
  { title: t('admin.login_ip'), key: 'ip', width: 140 },
  { title: t('admin.login_ua'), key: 'user_agent' }
]

onMounted(async () => {
  show.value = true
  try {
    const res: any = await getUserLoginLog({ uid: props.uid })
    loginLogList.value = res.data || []
  } catch (error) {
    console.error('获取登录日志失败:', error)
  }
})

const handleClose = () => props.onClose()
</script>

<style lang="scss" scoped>
.truncate-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 300px;
}
</style>

