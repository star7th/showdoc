<template>
  <header class="landing-header">
    <div class="header-content">
      <a href="/" class="logo">
        <span class="logo-text">ShowDoc</span>
      </a>
      <nav class="nav">
        <router-link to="/">{{ $t('index.home') || '首页' }}</router-link>
        <a href="https://www.showdoc.com.cn/clients" target="_blank">{{ $t('index.clients') || '客户端' }}</a>
        <div class="nav-separator"></div>
        <div class="toggle-group">
          <LanguageToggle />
          <ThemeToggle />
        </div>
        <template v-if="!userStore.isLoggedIn">
        <router-link to="/user/login">{{ $t('index.login_or_register') }}</router-link>
        </template>
        <router-link v-else to="/item/index" class="btn-primary">
          {{ $t('index.my_item') }}
        </router-link>
      </nav>
    </div>
  </header>
</template>

<script setup lang="ts">
import ThemeToggle from '@/components/ThemeToggle.vue'
import LanguageToggle from '@/components/LanguageToggle.vue'
import { useUserStore } from '@/store/user'

const userStore = useUserStore()
</script>

<style lang="scss" scoped>
@import './landing-common.scss';

.landing-header {
  @extend .landing-header;

  .nav {
    align-items: center;

    .nav-separator {
      width: 1px;
      height: 20px;
      background-color: var(--color-border);
      margin: 0 12px;
    }

    .toggle-group {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 0 8px;
    }
  }
}
</style>
