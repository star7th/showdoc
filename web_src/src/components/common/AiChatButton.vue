<template>
  <div>
    <!-- AI 对话窗口（已包含按钮和对话框） -->
    <AiChatDialog
      :item_id="item_id"
      :item_name="item_name"
      :visible.sync="showDialog"
      @update:visible="handleVisibleChange"
    ></AiChatDialog>
  </div>
</template>

<script>
import AiChatDialog from './AiChatDialog.vue'

export default {
  name: 'AiChatButton',
  components: {
    AiChatDialog
  },
  props: {
    item_id: {
      type: Number,
      default: 0
    },
    item_name: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      showDialog: true // 始终显示，AiChatDialog 内部会根据配置决定是否显示
    }
  },
  methods: {
    handleVisibleChange(newVal) {
      this.showDialog = newVal
    }
  }
}
</script>

<style scoped>
/* 样式已移至 AiChatDialog 组件 */
</style>

