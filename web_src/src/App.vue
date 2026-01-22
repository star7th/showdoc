<template>
  <div id="app-container" :data-theme="appStore.theme">
    <router-view />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useAppStore } from '@/store/app'

const appStore = useAppStore()

onMounted(() => {
  // 初始化主题
  appStore.initTheme()
})
</script>

<style lang="scss">
#app-container {
  width: 100%;
  height: 100%;
}
</style>

