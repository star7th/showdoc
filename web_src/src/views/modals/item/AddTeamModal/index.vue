<template>
  <div class="add-team-modal">
    <CommonModal
      :class="{ show }"
      :title="t('item.add_team')"
      :icon="['fas', 'fa-users']"
      width="500px"
      @close="handleClose"
    >
      <div class="modal-content">
        <a-form layout="vertical">
          <a-form-item :label="t('item.c_team')">
            <a-select
              v-model:value="form.team_id"
              :placeholder="t('item.please_choose')"
              :options="teamOptions"
            />
          </a-form-item>
        </a-form>

        <div class="create-team-section">
          <span class="create-tip">{{ t('item.or_create_team') }}</span>
          <span
            class="create-link"
            @click="handleCreateTeam"
          >
            {{ t('item.create_new_team') }}
          </span>
        </div>
      </div>

      <!-- 底部按钮 -->
      <template #footer>
        <div class="footer-buttons">
          <CommonButton
            :text="t('common.cancel')"
            theme="light"
            @click="handleClose"
          />
          <CommonButton
            :text="t('common.confirm')"
            theme="dark"
            @click="handleSubmit"
          />
        </div>
      </template>
    </CommonModal>
  </div>

  <!-- 团队管理弹窗 -->
  <TeamModal v-if="showTeamModal" />
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import TeamModalFunc from '@/views/modals/team/TeamModal'
import { getTeamList, saveTeamItem } from '@/models/team'

const { t } = useI18n()

const props = defineProps<{
  item_id: string | number
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const showTeamModal = ref(false)
const teams = ref<any[]>([])
const form = ref({
  team_id: ''
})

const teamOptions = computed(() => {
  return teams.value.map(team => ({
    label: team.team_name,
    value: team.id
  }))
})

// 获取团队列表
const fetchTeams = async () => {
  try {
    const res = await getTeamList()
    if (res.error_code === 0) {
      teams.value = res.data || []
    }
  } catch (error) {
    console.error('获取团队列表失败:', error)
  }
}

// 创建新团队
const handleCreateTeam = async () => {
  await TeamModalFunc()
  // 刷新团队列表
  await fetchTeams()
}

// 提交
const handleSubmit = async () => {
  if (!form.value.team_id) {
    message.warning(t('item.please_choose'))
    return
  }

  try {
    const res = await saveTeamItem(String(props.item_id), form.value.team_id)
    if (res.error_code === 0) {
      message.success(t('common.op_success'))
      handleClose(true)
    } else {
      message.error(res.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('添加团队失败:', error)
    message.error(t('common.op_failed'))
  }
}

// 关闭
const handleClose = (success = false) => {
  show.value = false
  setTimeout(() => {
    props.onClose(success)
  }, 300)
}

onMounted(() => {
  show.value = true
  fetchTeams()
})
</script>

<style scoped lang="scss">
.modal-content {
  padding: 20px;
}

.create-team-section {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid var(--color-border);
}

.create-tip {
  color: var(--color-text-secondary);
  font-size: 14px;
}

.create-link {
  color: var(--color-primary);
  cursor: pointer;
  text-decoration: underline;
  font-size: 14px;

  &:hover {
    text-decoration: underline;
    opacity: 0.8;
  }
}

.footer-buttons {
  display: flex;
  justify-content: center;
  align-items: center;

  :deep(.common-button) {
    width: 160px;
    margin: 0 7.5px;
  }
}

:deep(.ant-select) {
  width: 100%;
}

:deep(.ant-form-item) {
  margin-bottom: 0;
}

:deep(.ant-form-item-label) {
  padding-bottom: 8px;
}

[data-theme="dark"] {
  :deep(.ant-select) {
    .ant-select-selector {
      background-color: var(--color-bg-secondary);
      color: var(--color-text-primary);
      border-color: var(--color-border);
    }

    .ant-select-selection-item {
      color: var(--color-text-primary);
    }

    &.ant-select-focused .ant-select-selector {
      border-color: var(--color-active);
    }
  }
}
</style>

