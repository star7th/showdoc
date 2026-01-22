<template>
  <div class="user-setting-page"></div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import { onMounted } from 'vue'
import UserCenterModal from '@/views/modals/user/UserCenterModal'

const router = useRouter()

const handleModalClose = () => {
  // 直接跳转到项目列表，因为用户中心关闭后应该回到主工作区
  router.push('/item/index')
}

onMounted(async () => {
  await UserCenterModal()
  handleModalClose()
})
</script>

<style lang="scss" scoped>
.user-setting-page {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1000;
}
</style>
