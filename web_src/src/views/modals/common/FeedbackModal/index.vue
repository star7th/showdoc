<template>
  <div class="feedback-modal">
    <CommonModal
      v-if="show"
      :class="{ show }"
      :title="$t('feedback.feedback')"
      :icon="['fas', 'circle-info']"
      maxWidth="500px"
      @close="closeHandle"
    >
      <div class="modal-content">
        <div class="feedback-info">
          <div class="info-text">
            <span>{{ $t('feedback.feedbackDescription') }}</span>
          </div>
          <div class="link-box">
            <div class="github-link" @click="toGithub">
              <span class="link-text">{{ $t('feedback.githubLinkText') }}</span>
            </div>
            <div class="star-tip">
              <i class="fas fa-star"></i>
              <span>{{ $t('feedback.starTip') }}</span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <div class="secondary-button" @click="closeHandle">
          {{ $t('common.cancel') }}
        </div>
        <div class="primary-button" @click="toGithub">
          {{ $t('feedback.goToGithub') }}
        </div>
      </div>
    </CommonModal>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import CommonModal from '@/components/CommonModal.vue'
import Message from '@/components/Message'

const { t } = useI18n()

const props = defineProps<{
  onClose: (res: boolean) => void
}>()

const show = ref(false)
const githubIssuesUrl = 'https://github.com/star7th/showdoc/issues'

const toGithub = () => {
  window.open(githubIssuesUrl, '_blank')
}

const closeHandle = () => {
  show.value = false
  setTimeout(() => {
    props.onClose(false)
  }, 300)
}

onMounted(() => {
  setTimeout(() => {
    show.value = true
  })
})
</script>

<style lang="scss" scoped>
.modal-content {
  padding: 30px 50px;
  border-bottom: 1px solid var(--color-interval);
}

.feedback-info {
  .info-text {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 20px;
    line-height: 1.6;
    color: var(--color-text-primary);

    i {
      color: var(--color-active);
      margin-top: 3px;
      font-size: 18px;
    }
  }

  .link-box {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .github-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background-color: var(--color-bg-secondary);
    border-radius: 8px;
    border: 1px solid var(--color-border);
    cursor: pointer;
    transition: all 0.15s ease;

    i {
      color: var(--color-text-primary);
      font-size: 18px;
    }

    .link-text {
      font-family: 'Monaco', 'Consolas', monospace;
      color: var(--color-active);
      font-size: 14px;
      word-break: break-all;
    }

    &:hover {
      background-color: var(--hover-overlay);
      border-color: var(--color-active);
      box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
    }

    &:active {
      transform: scale(0.98);
    }
  }

  .star-tip {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background-color: var(--color-yellow-bg);
    border-radius: 8px;
    border: var(--color-yellow-border);
    color: var(--color-yellow-text);
    font-size: 13px;
    line-height: 1.5;

    i {
      color: var(--color-orange);
      font-size: 14px;
    }
  }
}

// 暗黑主题适配
[data-theme='dark'] {
  .feedback-info {
    .github-link {
      background-color: var(--color-bg-secondary);
      border-color: var(--color-border);

      &:hover {
        background-color: var(--hover-overlay);
      }
    }
  }
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 90px;

  .primary-button,
  .secondary-button {
    width: 160px;
    margin: 0 7.5px;
  }
}
</style>

