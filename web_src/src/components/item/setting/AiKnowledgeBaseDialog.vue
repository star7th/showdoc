<template>
  <div class="hello">
    <SDialog
      :onCancel="handleClose"
      :title="$t('ai_knowledge_base')"
      width="650px"
      :showCancel="true"
      :cancelText="$t('close')"
      :showOk="false"
      top="5vh"
    >
      <!-- 直接使用 AiKnowledgeBase 组件，避免代码重复 -->
      <AiKnowledgeBase
        :itemId="item_id"
        @need-refresh="handleNeedRefresh"
      ></AiKnowledgeBase>
    </SDialog>
  </div>
</template>

<script>
import AiKnowledgeBase from '@/components/item/setting/AiKnowledgeBase'

export default {
  name: 'AiKnowledgeBaseDialog',
  components: {
    AiKnowledgeBase
  },
  props: {
    callback: {
      type: Function,
      required: false,
      default: () => {}
    },
    item_id: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      needRefresh: false // 是否需要刷新页面
    }
  },
  methods: {
    // 处理需要刷新的事件
    handleNeedRefresh() {
      this.needRefresh = true
    },
    // 处理对话框关闭
    handleClose() {
      // 先调用原来的回调
      if (this.callback) {
        this.callback()
      }
      // 如果需要刷新，延迟一下再刷新（让对话框先关闭）
      if (this.needRefresh) {
        setTimeout(() => {
          window.location.reload()
        }, 300)
      }
    }
  }
}
</script>

<style scoped>
/* 样式由 AiKnowledgeBase 组件提供 */
</style>

