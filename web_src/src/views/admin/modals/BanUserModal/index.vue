<template>
  <CommonModal :show="show" :title="$t('admin.ban_user_confirm')" @close="handleClose">
    <CommonInput
      v-model="banReason"
      :placeholder="$t('admin.ban_reason_placeholder')"
    />
    <template #footer>
      <CommonButton theme="light" :text="$t('common.cancel')" @click="handleClose" />
      <CommonButton theme="dark" :text="$t('common.confirm')" @click="handleConfirm" />
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
import { banUser } from '@/models/admin'

const { t } = useI18n()

const props = defineProps<{
  uid: number
  onClose: (result: boolean, remark?: string) => void
}>()

const show = ref(false)
const banReason = ref('')

const handleClose = () => props.onClose(false)
const handleConfirm = () => {
  props.onClose(true, banReason.value)
}

onMounted(() => {
  show.value = true
})
</script>

