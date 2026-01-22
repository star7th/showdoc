<template>
  <CommonModal :show="show" :title="$t('admin.attorn')" width="300px" @close="handleClose">
    <CommonInput
      v-model="username"
      :placeholder="$t('admin.attorn_username_placeholder')"
    />
    <template #footer>
      <CommonButton theme="light" :text="$t('common.cancel')" @click="handleClose" />
      <CommonButton theme="dark" :text="$t('admin.attorn')" @click="handleConfirm" />
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonInput from '@/components/CommonInput.vue'
import CommonButton from '@/components/CommonButton.vue'

const { t } = useI18n()

const props = defineProps<{
  item_id: number
  onClose: (result: boolean, data: any) => void
}>()

const show = ref(false)
const username = ref('')

const handleClose = () => props.onClose(false, null)
const handleConfirm = () => props.onClose(true, { username: username.value })

onMounted(() => {
  show.value = true
})
</script>

