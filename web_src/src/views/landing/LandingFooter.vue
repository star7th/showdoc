<template>
  <footer class="landing-footer">
    <div class="footer-content">
      <div class="footer-section">
        <h4>ShowDoc</h4>
        <p>{{ $t('index.footer_slogan') || '让文档协作更简单' }}</p>
      </div>
      <div class="footer-section">
        <h4>{{ $t('index.about_us') || '关于我们' }}</h4>
        <a href="https://github.com/star7th/showdoc" target="_blank">{{ $t('index.about') || '关于' }}</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>本站基于开源项目<a href="https://github.com/star7th/showdoc" target="_blank">showdoc</a>构建</span>
      <a v-if="beian" href="https://beian.miit.gov.cn/" target="_blank">{{ beian }}</a>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import request from '@/utils/request'

const beian = ref('')

// 获取首页设置（包括备案号）
onMounted(async () => {
  try {
    const res: any = await request('/api/common/homePageSetting', {}, 'post', false)
    if (res && res.data && res.data.beian) {
      beian.value = res.data.beian
    }
  } catch (error) {
    console.error('Failed to load home page setting:', error)
  }
})
</script>

<style lang="scss" scoped>
@import './landing-common.scss';

.landing-footer {
  @extend .landing-footer;
}
</style>
