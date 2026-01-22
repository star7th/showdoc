<template>
  <div class="page-edit-page"></div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import EditPageModal from '@/views/modals/page/EditPageModal/index'
import { useAppStore } from '@/store'

const route = useRoute()
const router = useRouter()
const appStore = useAppStore()

// 从路由参数获取信息（使用字符串避免大整数精度丢失）
const itemId = route.params.item_id as string
const pageId = route.params.page_id as string
const catId = (route.query.cat_id as string) || ''

onMounted(async () => {
  // 打开编辑弹窗
  const result = await EditPageModal({
    itemId,
    editPageId: pageId,
    catId
  })

  // 根据结果处理
  if (result && pageId) {
    // 编辑模式：刷新当前页面
    window.location.reload()
  } else if (result && !pageId) {
    // 新建模式：返回上一页（页面ID在弹窗内已更新）
    router.back()
  } else {
    // 取消：返回上一页
    router.back()
  }
})
</script>

<style lang="scss" scoped>
.page-edit-page {
  min-height: 100vh;
  background-color: var(--color-bg-secondary);
}
</style>
