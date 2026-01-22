<template>
  <CommonModal
    :show="show"
    :title="$t('page.beautify_json')"
    width="600px"
    @close="handleClose"
  >
    <div class="json-beautify">
      <CommonTextarea
        v-model="jsonContent"
        :label="$t('page.json_content')"
        :placeholder="$t('page.json_content_placeholder')"
        :rows="8"
      />
    </div>

    <template #footer>
      <CommonButton @click="handleClose">{{ $t('common.cancel') }}</CommonButton>
      <CommonButton theme="dark" @click="handleBeautify">{{ $t('common.confirm') }}</CommonButton>
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import Message from '@/components/Message'
import AlertModal from '@/components/AlertModal'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonTextarea from '@/components/CommonTextarea.vue'

interface Props {
  onClose: () => void
  onInsert: (json: string) => void
}

const props = defineProps<Props>()

// Composables
const { t } = useI18n()

// Refs
const show = ref(false)
const jsonContent = ref('')

// Methods
const handleClose = () => {
  props.onClose()
}

const handleBeautify = () => {
  try {
    const jsonData = JSON.parse(jsonContent.value)
    const beautified = JSON.stringify(jsonData, null, 2)
    props.onInsert(`\`\`\`json\n${beautified}\n\`\`\``)
  } catch (error) {
    AlertModal(t('page.invalid_json'))
  }
}

// Lifecycle
onMounted(() => {
  show.value = true
})
</script>

<style scoped lang="scss">
.json-beautify {
  padding: 10px 0;
}
</style>
