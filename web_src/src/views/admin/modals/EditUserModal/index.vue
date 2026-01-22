<template>
  <CommonModal :show="show" :title="user ? t('admin.edit_user') : t('admin.add_user')" width="400px" @close="handleClose">
    <div class="modal-form">
      <div class="form-group">
        <label class="form-label">{{ t('admin.username') }}</label>
        <CommonInput
          v-model="form.username"
          :placeholder="t('admin.username_placeholder')"
          :disabled="!!user"
        />
      </div>
      <div class="form-group">
        <label class="form-label">{{ t('admin.name') }}</label>
        <CommonInput
          v-model="form.name"
          :placeholder="t('admin.name_placeholder')"
        />
      </div>
      <div class="form-group">
        <label class="form-label">{{ t('user.password') }}</label>
        <input
          v-model="form.password"
          type="password"
          class="password-input"
          :placeholder="user ? t('admin.password_tip') : t('user.password')"
        />
      </div>
    </div>
    <template #footer>
      <div class="modal-footer">
        <CommonButton
          theme="light"
          :text="t('common.cancel')"
          @click="handleClose"
        />
        <CommonButton
          theme="dark"
          :text="t('common.confirm')"
          @click="handleConfirm"
        />
      </div>
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { message } from 'ant-design-vue'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonInput from '@/components/CommonInput.vue'
import { saveUser } from '@/models/admin'

const { t } = useI18n()

const props = defineProps<{
  user?: any
  onClose: (result: boolean) => void
}>()

const show = ref(false)
const form = reactive({
  username: '',
  name: '',
  password: '',
  uid: ''
})

onMounted(() => {
  show.value = true
  if (props.user) {
    form.username = props.user.username || ''
    form.name = props.user.name || ''
    form.uid = props.user.uid
  }
})

const handleClose = () => props.onClose(false)
const handleConfirm = async () => {
  try {
    const params: any = {
      username: form.username,
      name: form.name,
      uid: form.uid
    }
    // 编辑用户时，如果密码为空则不传；添加用户时必须传密码
    if (props.user) {
      if (form.password) {
        params.password = form.password
      }
    } else {
      params.password = form.password
    }
    await saveUser(params)
    message.success(t('common.save_success'))
    props.onClose(true)
  } catch (error) {
    message.error(t('common.save_failed'))
  }
}
</script>

<style lang="scss" scoped>
.modal-form {
  .form-group {
    margin-bottom: 16px;
    
    .form-label {
      display: block;
      margin-bottom: 8px;
      font-size: 14px;
      font-weight: 600;
      color: var(--color-text-primary);
    }
  }

  .password-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid var(--color-border);
    border-radius: 4px;
    font-size: 14px;
    background: var(--color-bg-primary);
    color: var(--color-text-primary);
    transition: all 0.15s ease;

    &:focus {
      outline: none;
      border-color: var(--color-active);
      box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
    }
  }
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
</style>

