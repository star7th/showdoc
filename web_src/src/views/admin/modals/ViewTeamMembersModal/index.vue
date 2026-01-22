<template>
  <CommonModal :show="show" :title="$t('admin.team_members')" width="650px" :show-footer="false" @close="handleClose">
    <CommonTable
      v-if="teamMembers.length > 0"
      :table-header="teamMemberHeader"
      :table-data="teamMembers"
      :pagination="false"
      row-key="id"
      max-height="400px"
    />
    <p v-else class="tip-text">{{ $t('admin.no_team_member') }}</p>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonTable from '@/components/CommonTable.vue'
import { getTeamMemberList } from '@/models/team'

const { t } = useI18n()

const props = defineProps<{
  team_id: number
  onClose: () => void
}>()

const show = ref(false)
const teamMembers = ref<any[]>([])

const teamMemberHeader = computed(() => [
  { title: t('admin.username'), key: 'member_username', width: 140 },
  { title: t('user.name'), key: 'name', width: 140 },
  { title: t('admin.join_time'), key: 'addtime', width: 160 }
])

const fetchTeamMembers = async () => {
  try {
    const res: any = await getTeamMemberList(props.team_id)
    teamMembers.value = res.data || []
  } catch (error) {
    console.error('获取团队成员失败:', error)
  }
}

const handleClose = () => props.onClose()

onMounted(() => {
  show.value = true
  fetchTeamMembers()
})
</script>

<style lang="scss" scoped>
.tip-text {
  color: var(--color-text-secondary);
  font-size: 13px;
  line-height: 1.6;
}
</style>


