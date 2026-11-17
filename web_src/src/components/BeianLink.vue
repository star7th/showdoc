<template>
  <a
    v-if="beian"
    class="beian-link"
    href="https://beian.miit.gov.cn/"
    target="_blank"
  >{{ beian }}</a>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import request from '@/utils/request'

// 备案号：来自后台系统设置（options 表 beian 字段，经 /api/common/homePageSetting 返回）
// 为空时不渲染任何内容
const beian = ref('')

onMounted(async () => {
  try {
    const res: any = await request('/api/common/homePageSetting', {}, 'post', false)
    if (res && res.error_code === 0 && res.data && res.data.beian) {
      beian.value = String(res.data.beian)
    }
  } catch (e) {
    // 获取失败时不渲染备案号，不影响页面
  }
})
</script>

<style lang="scss" scoped>
.beian-link {
  color: var(--color-text-secondary);
  text-decoration: none;

  &:hover {
    color: var(--color-active);
  }
}
</style>
