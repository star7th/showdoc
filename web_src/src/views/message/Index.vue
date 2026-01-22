<template>
  <div class="message-page"></div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import MessageModal from '@/views/modals/message/MessageModal'

const route = useRoute()

onMounted(() => {
  // 获取查询参数中的 dtab，自动弹出消息弹窗
  const dtab = route.query.dtab as string
  MessageModal(dtab)
})
</script>

<style lang="scss" scoped>
.message-page {
  display: none;
}
</style>

