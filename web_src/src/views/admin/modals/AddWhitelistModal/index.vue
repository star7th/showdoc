<template>
  <CommonModal :show="show" :title="$t('admin.add_whitelist_reason')" width="400px" @close="handleClose">
    <CommonTextarea
      v-model="remark"
      :placeholder="$t('admin.remark_placeholder')"
      :rows="4"
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
import CommonModal from '@/components/CommonModal.vue'
import CommonTextarea from '@/components/CommonTextarea.vue'
import CommonButton from '@/components/CommonButton.vue'

const { t } = useI18n()

const props = defineProps<{
  item_id: number
  item_name?: string
  onClose: (result: boolean, data: any) => void
}>()

const show = ref(false)
const remark = ref('')

const handleClose = () => props.onClose(false, null)
const handleConfirm = () => props.onClose(true, { remark: remark.value })

onMounted(() => {
  show.value = true
})
</script>

