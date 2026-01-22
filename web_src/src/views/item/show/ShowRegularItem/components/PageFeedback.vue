<template>
  <div class="page-feedback" v-if="showFeedback">
    <div class="feedback-title">{{ $t('pageFeedback.title') }}</div>
    <div class="feedback-buttons">
      <CommonButton
        :type="userFeedback === 1 ? 'primary' : 'default'"
        size="small"
        :loading="submitting"
        @click="submitFeedback(1)"
      >
        <i class="fas fa-thumbs-up"></i>
        {{ $t('pageFeedback.helpful') }} ({{ helpfulCount }})
      </CommonButton>
      <CommonButton
        :type="userFeedback === 2 ? 'primary' : 'default'"
        size="small"
        :loading="submitting"
        @click="submitFeedback(2)"
      >
        <i class="fas fa-thumbs-down"></i>
        {{ $t('pageFeedback.unhelpful') }} ({{ unhelpfulCount }})
      </CommonButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { getFeedbackStat, submitFeedback as submitFeedbackApi } from '@/models/pageFeedback'
import Message from '@/components/Message'
import CommonButton from '@/components/CommonButton.vue'

// Props
interface Props {
  pageId?: number
  itemInfo?: any
}

const props = withDefaults(defineProps<Props>(), {
  pageId: 0,
  itemInfo: () => ({})
})

// Composables
const { t } = useI18n()

// Refs
const helpfulCount = ref(0)
const unhelpfulCount = ref(0)
const userFeedback = ref(0) // 0=未反馈，1=有帮助，2=没有帮助
const submitting = ref(false)
const showFeedback = ref(false)
let loadTimer: number | null = null

// Methods
const loadFeedbackStat = async () => {
  if (!props.pageId) return

  try {
    const res = await getFeedbackStat(props.pageId)
    if (res.error_code === 0 && res.data) {
      helpfulCount.value = res.data.helpful_count || 0
      unhelpfulCount.value = res.data.unhelpful_count || 0
      userFeedback.value = res.data.user_feedback || 0
    }
  } catch (err) {
    console.error('加载反馈统计失败:', err)
  }
}

const submitFeedback = async (feedbackType: number) => {
  if (!props.pageId) return

  // 如果点击的是已选中的按钮，则取消反馈
  if (userFeedback.value === feedbackType) {
    feedbackType = 0 // 取消反馈
  }

  submitting.value = true
  try {
    const res = await submitFeedbackApi({
      page_id: props.pageId,
      feedback_type: feedbackType
    })
    if (res.error_code === 0 && res.data) {
      helpfulCount.value = res.data.helpful_count || 0
      unhelpfulCount.value = res.data.unhelpful_count || 0
      userFeedback.value = res.data.user_feedback || 0
      if (res.data.message) {
        Message.success(res.data.message)
      }
    }
  } catch (err) {
    console.error('提交反馈失败:', err)
  } finally {
    submitting.value = false
  }
}

const checkAllowFeedback = () => {
  if (loadTimer) {
    clearTimeout(loadTimer)
    loadTimer = null
  }

  if (props.pageId && props.itemInfo) {
    // 检查是否开启反馈功能（使用弱等于，因为后端可能返回字符串）
    const allow = props.itemInfo.allow_feedback == 1 || props.itemInfo.allow_feedback === true
    if (allow) {
      showFeedback.value = true
      // 延迟加载，确保页面渲染完成
      loadTimer = window.setTimeout(() => {
        loadFeedbackStat()
      }, 1000)
    } else {
      showFeedback.value = false
    }
  } else {
    showFeedback.value = false
  }
}

// Watchers
watch(() => props.pageId, checkAllowFeedback, { immediate: true })
watch(
  () => props.itemInfo,
  checkAllowFeedback,
  { deep: true, immediate: true }
)

// Lifecycle
onBeforeUnmount(() => {
  if (loadTimer) {
    clearTimeout(loadTimer)
    loadTimer = null
  }
})
</script>

<style scoped lang="scss">
.page-feedback {
  margin: 30px 0;
  padding: 20px;
  border-top: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);
}

.feedback-title {
  margin-bottom: 15px;
  font-size: 14px;
  color: var(--color-text-secondary);
}

.feedback-buttons {
  display: flex;
  gap: 10px;

  i {
    margin-right: 5px;
  }
}
</style>

