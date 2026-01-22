<template>
  <CommonModal
    :show="show"
    :title="$t('page.sql_to_markdown_table')"
    width="700px"
    @close="handleClose"
  >
    <div class="sql-to-markdown">
      <div class="description">
        {{ $t('page.sql_to_markdown_table_description') }}
      </div>
      <CommonTextarea
        v-model="sqlContent"
        :placeholder="$t('page.sql_content_placeholder')"
        :rows="10"
      />
    </div>

    <template #footer>
      <CommonButton @click="handleClose">{{ $t('common.cancel') }}</CommonButton>
      <CommonButton type="primary" :loading="converting" @click="handleConvert">{{ $t('common.confirm') }}</CommonButton>
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
import request from '@/utils/request'

interface Props {
  onClose: () => void
  onInsert: (table: string) => void
}

const props = defineProps<Props>()

// Composables
const { t } = useI18n()

// Refs
const show = ref(false)
const sqlContent = ref('')
const converting = ref(false)

// Methods
const handleClose = () => {
  props.onClose()
}

const handleConvert = async () => {
  if (!sqlContent.value.trim()) {
    await AlertModal(t('common.required'))
    return
  }

  converting.value = true

  try {
    const response = await request('/api/page/sqlToMarkdownTable', {
      sql: sqlContent.value
    }, 'post', false)

    if (response.error_code === 0 && response.data && response.data.markdown) {
      props.onInsert(response.data.markdown)
      props.onClose()
      Message.success(t('common.op_success'))
    } else {
      await AlertModal(response.error_message || t('common.op_failed'))
    }
  } catch (error) {
    console.error('SQL 转换失败:', error)
    await AlertModal(t('common.op_failed'))
  } finally {
    converting.value = false
  }
}

// Lifecycle
onMounted(() => {
  show.value = true
})
</script>

<style scoped lang="scss">
.sql-to-markdown {
  padding: 10px 0;

  .description {
    margin-bottom: 12px;
    padding: 12px;
    background-color: var(--color-bg-secondary);
    border-left: 3px solid var(--color-primary);
    border-radius: 4px;
    color: var(--color-text-primary);
    font-size: 13px;
    line-height: 1.6;
  }
}
</style>
