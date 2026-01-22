<template>
  <div class="attorn-team-modal">
    <CommonModal
      :class="{ show }"
      :title="t('item.attorn_team')"
      :icon="['fas', 'user-group']"
      width="500px"
      @close="handleClose"
    >
      <div class="form-content">
        <div class="form-item">
          <div class="form-label">{{ t('item.attorn_username') }}</div>
          <CommonInput
            v-model="attornForm.username"
            :placeholder="t('item.attorn_username')"
            auto-complete="new-password"
          />
        </div>
        <div class="form-item">
          <div class="form-label">{{ t('item.input_login_password') }}</div>
          <CommonInput
            v-model="attornForm.password"
            type="password"
            :placeholder="t('item.input_login_password')"
            auto-complete="new-password"
          />
        </div>
        <div class="tips-text">
          {{ t('item.attornTeamTips') }}
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
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonInput from '@/components/CommonInput.vue'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import { attornTeam } from '@/models/team'

const { t } = useI18n()

const props = defineProps<{
  team_id: number
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const attornForm = ref({
  username: '',
  password: ''
})

// 提交转让
const handleSubmit = async () => {
  if (!attornForm.value.username.trim()) {
    await AlertModal(t('item.attorn_username') + t('common.required'))
    return
  }
  if (!attornForm.value.password.trim()) {
    await AlertModal(t('item.input_login_password') + t('common.required'))
    return
  }

  try {
    const res = await attornTeam(
      props.team_id,
      attornForm.value.username,
      attornForm.value.password
    )

    if (res.error_code === 0) {
      Message.success(t('common.op_success'))
      handleClose()
      props.onClose(true)
    }
  } catch (error) {
    console.error('转让团队失败:', error)
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

.tips-text {
  font-size: 12px;
  color: var(--color-text-secondary);
  margin-top: 12px;
  padding: 8px 12px;
  background-color: var(--color-bg-secondary);
  border-radius: 4px;
  line-height: 1.5;
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

// 暗黑主题适配
[data-theme='dark'] {
  .tips-text {
    background-color: var(--color-bg-secondary);
  }
}
</style>

