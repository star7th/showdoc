<template>
  <CommonModal :show="show" :title="$t('admin.add_member')" width="400px" top="10vh" @close="handleClose">
    <div class="modal-form">
      <label class="form-label">{{ $t('admin.member_username') }}</label>
      <CommonInput
        v-model="username"
        :placeholder="$t('admin.input_member_username')"
      />
    </div>
    <template #footer>
      <div class="modal-footer">
        <CommonButton theme="light" :text="$t('common.cancel')" @click="handleClose" />
        <CommonButton theme="dark" :text="$t('common.confirm')" @click="handleConfirm" />
      </div>
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'
import { saveMember } from '@/models/member'

const { t } = useI18n()

const props = defineProps<{
  item_id: number
  onClose: (result: boolean, username?: string) => void
}>()

const show = ref(false)
const username = ref('')

const handleClose = () => props.onClose(false)

const handleConfirm = async () => {
  if (!username.value.trim()) {
    message.warning(t('admin.input_member_username'))
    return
  }
  try {
    await saveMember({
      item_id: String(props.item_id),
      username: username.value,
      member_group_id: 1
    })
    message.success(t('admin.add_member_success'))
    props.onClose(true, username.value)
  } catch (error) {
    message.error(t('common.op_failed'))
  }
}

onMounted(() => {
  show.value = true
})
</script>

<style lang="scss" scoped>
.modal-form {
  .form-label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--color-text-primary);
  }
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
</style>


