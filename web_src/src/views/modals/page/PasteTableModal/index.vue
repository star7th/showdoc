<template>
  <CommonModal
    :show="show"
    :title="$t('page.paste_insert_table')"
    width="600px"
    @close="handleClose"
  >
    <div class="paste-table">
      <CommonTextarea
        v-model="tableContent"
        :label="$t('page.table_content')"
        :placeholder="$t('page.table_content_placeholder')"
        :rows="8"
      />
    </div>

    <template #footer>
      <CommonButton @click="handleClose">{{ $t('common.cancel') }}</CommonButton>
      <CommonButton type="primary" @click="handleConvert">{{ $t('common.confirm') }}</CommonButton>
    </template>
  </CommonModal>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import CommonButton from '@/components/CommonButton.vue'
import CommonTextarea from '@/components/CommonTextarea.vue'

interface Props {
  onClose: () => void
  onInsert: (table: string) => void
}

const props = defineProps<Props>()

// Composables
const { t } = useI18n()

// Refs
const show = ref(false)
const tableContent = ref('')

// Methods
const handleClose = () => {
  props.onClose()
}

const handleConvert = () => {
  const lines = tableContent.value.trim().split('\n')
  if (lines.length === 0) return

  // 将制表符分隔的数据转换为 Markdown 表格
  const rows = lines.map(line => line.split('\t'))
  const maxCols = Math.max(...rows.map(row => row.length))

  // 构建表头
  let table = '| ' + rows[0].map(cell => cell.trim()).join(' | ') + ' |\n'
  table += '| ' + Array(maxCols).fill('---').join(' | ') + ' |\n'

  // 添加数据行
  rows.slice(1).forEach(row => {
    const paddedRow = Array(maxCols).fill('').map((_, i) => (row[i] || '').trim())
    table += '| ' + paddedRow.join(' | ') + ' |\n'
  })

  props.onInsert(table)
}

// Lifecycle
onMounted(() => {
  show.value = true
})
</script>

<style scoped lang="scss">
.paste-table {
  padding: 10px 0;
}
</style>
