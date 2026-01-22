<template>
  <div>
    <!-- AI 对话窗口（已包含按钮和对话框） -->
    <AiChatDialog
      :item_id="itemId"
      :item_name="itemName"
      :visible="showDialog"
      @update:visible="handleVisibleChange"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import AiChatDialog from './AiChatDialog.vue'

interface Props {
  itemId?: number
  itemName?: string
}

const props = withDefaults(defineProps<Props>(), {
  itemId: 0,
  itemName: ''
})

const showDialog = ref(false)

// Watch itemId 变化，重置对话框状态
watch(() => props.itemId, () => {
  showDialog.value = false
})

const handleVisibleChange = (newVal: boolean) => {
  showDialog.value = newVal
}
</script>

<style scoped>
/* 样式已移至 AiChatDialog 组件 */
</style>

