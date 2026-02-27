<template>
  <div class="about-site">
    <div class="about-content">
      <div class="info-section">
        <h3 class="section-title">{{ $t('admin.version') }}</h3>
        <p class="info-text">
          {{ $t('admin.open_source_based') }} <b>{{ $t('admin.current_version') }}: {{ curVersion }}</b>
        </p>
      </div>

      <div class="info-section">
        <h3 class="section-title">GitHub</h3>
        <p class="info-text">
          {{ $t('admin.issue_report') }}：<a href="https://github.com/star7th/showdoc/issues" target="_blank" class="link">
            https://github.com/star7th/showdoc/issues
          </a>
          <br />
          {{ $t('admin.star_tip') }}
          <a href="https://github.com/star7th/showdoc" target="_blank" class="link">GitHub</a>
        </p>
      </div>

      <div class="info-section">
        <h3 class="section-title">{{ $t('common.copyright') }}</h3>
        <p class="info-text">
          <a href="https://www.showdoc.com.cn/" target="_blank" class="link">{{ $t('common.showdoc') }}</a>
          {{ $t('admin.copyright_info') }}
        </p>
      </div>

      <div v-if="hasUpdate" class="update-section">
        <hr />
        <h3 class="section-title update-title">
          {{ $t('admin.update_available') }}({{ newVersion }})
          <span class="new-badge">NEW</span>
        </h3>
        <p class="info-text">
          {{ $t('admin.update_content') }}
          <a href="https://www.showdoc.com.cn/help/13733" target="_blank" class="link">{{ $t('admin.update_log') }}</a>
        </p>
        <p class="info-text">{{ $t('admin.update_tips') }}</p>
        <CommonButton type="primary" @click="handleUpdate">{{ $t('admin.click_to_update') }}</CommonButton>
        <br />
        <br />
        <p class="info-text">
          {{ $t('admin.update_failed_tip') }}，
          <a href="https://www.showdoc.com.cn/help?page_id=13732" target="_blank" class="link">{{ $t('common.click_here') }}</a>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { useI18n } from 'vue-i18n'
import request from '@/utils/request'
import CommonButton from '@/components/CommonButton.vue'

const { t } = useI18n()

const curVersion = ref('')
const newVersion = ref('')
const hasUpdate = ref(false)
const updateUrl = ref('')

// 获取当前版本
const getCurVersion = async () => {
  try {
    const res = await request('/api/common/version', {}, 'get')
    if (res && res.data && res.data.version) {
      curVersion.value = res.data.version
    }
  } catch (error) {
    console.error('Failed to get current version:', error)
  }
}

// 检查更新
const checkUpdate = async () => {
  try {
    const res = await request('/api/adminUpdate/checkUpdate', {}, 'get')
    if (res && res.data && res.data.url) {
      updateUrl.value = res.data.url
      newVersion.value = res.data.new_version
      curVersion.value = res.data.version
      hasUpdate.value = true
    }
  } catch (error) {
    console.error('Failed to check update:', error)
  }
}

// 执行更新
const handleUpdate = async () => {
  const loadingMessage = message.loading(t('common.downloading_update_package'), 0)
  try {
    // 下载更新包
    await request('/api/adminUpdate/download', {}, 'post')

    loadingMessage()
    const updatingMessage = message.loading(t('common.updating_files'), 0)

    // 更新文件
    await request('/api/adminUpdate/updateFiles', {}, 'post')

    updatingMessage()
    const refreshingMessage = message.loading(
      t('common.upgrade_success_ready_to_refresh') +
      t('common.if_cache_not_auto_update_refresh_manually'),
      0
    )

    // 检查数据库
    await request('/api/update/checkDb', {}, 'post')

    // 延迟刷新页面
    setTimeout(() => {
      window.location.reload()
    }, 7000)
  } catch (error) {
    loadingMessage()
    message.error(t('common.update_failed'))
    console.error('Update failed:', error)
  }
}

onMounted(() => {
  getCurVersion()
  checkUpdate()
})
</script>

<style lang="scss" scoped>
.about-site {
  padding: 24px;
  background-color: var(--color-default);

  .about-content {
    max-width: 800px;
    margin: 0 auto;

    .info-section {
      margin-bottom: 32px;

      .section-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--color-primary);
        margin-bottom: 12px;
      }

      .info-text {
        font-size: 14px;
        line-height: 1.8;
        color: var(--color-primary);
        margin: 0;

        b {
          font-weight: 600;
        }

        .link {
          color: var(--color-active);
          text-decoration: none;
          transition: color 0.15s ease;

          &:hover {
            color: var(--hover-overlay);
            text-decoration: underline;
          }
        }
      }
    }

    .update-section {
      padding: 24px;
      background-color: var(--color-secondary);
      border-radius: 8px;
      border: 1px solid var(--color-interval);

      hr {
        border: none;
        border-top: 1px solid var(--color-interval);
        margin: 24px 0;
      }

      .update-title {
        color: var(--color-active);
        display: flex;
        align-items: center;
        gap: 8px;

        .new-badge {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          padding: 2px 8px;
          font-size: 11px;
          font-weight: 600;
          color: #fff;
          background-color: var(--color-red);
          border-radius: 4px;
          animation: pulse 2s infinite;
        }
      }
    }
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

// 暗黑主题适配
[data-theme='dark'] {
  .about-site {
    .about-content {
      .update-section {
        background-color: var(--color-obvious);
      }
    }
  }
}
</style>
