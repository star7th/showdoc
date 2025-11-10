<template>
  <div class="page-feedback" v-if="showFeedback" id="feedback-area">
    <div class="feedback-title">{{ $t('pageFeedback.title') }}</div>
    <el-button-group>
      <el-button
        :type="userFeedback === 1 ? 'primary' : 'default'"
        size="small"
        :loading="submitting"
        @click="submitFeedback(1)"
      >
        <i class="el-icon-thumb"></i> {{ $t('pageFeedback.helpful') }} ({{
          helpfulCount
        }})
      </el-button>
      <el-button
        :type="userFeedback === 2 ? 'warning' : 'default'"
        size="small"
        :loading="submitting"
        @click="submitFeedback(2)"
      >
        <i class="el-icon-warning-outline"></i>
        {{ $t('pageFeedback.unhelpful') }} ({{ unhelpfulCount }})
      </el-button>
    </el-button-group>
  </div>
</template>

<script>
import { getFeedbackStat, submitFeedback } from '@/models/pageFeedback'

export default {
  name: 'PageFeedback',
  props: {
    pageId: {
      type: [Number, String],
      default: 0
    },
    itemInfo: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      helpfulCount: 0,
      unhelpfulCount: 0,
      userFeedback: 0, // 0=未反馈，1=有帮助，2=没有帮助
      submitting: false,
      showFeedback: false,
      loadTimer: null
    }
  },
  watch: {
    pageId: {
      immediate: true,
      handler(newVal) {
        // 清除之前的定时器
        if (this.loadTimer) {
          clearTimeout(this.loadTimer)
          this.loadTimer = null
        }
        if (
          newVal &&
          this.itemInfo &&
          (this.itemInfo.allow_feedback == 1 ||
            this.itemInfo.allow_feedback === true)
        ) {
          this.showFeedback = true
          // 延迟1秒加载，确保页面渲染完成
          this.loadTimer = setTimeout(() => {
            this.loadFeedbackStat()
          }, 1000)
        } else {
          this.showFeedback = false
        }
      }
    },
    itemInfo: {
      deep: true,
      handler(newVal) {
        // 清除之前的定时器
        if (this.loadTimer) {
          clearTimeout(this.loadTimer)
          this.loadTimer = null
        }
        if (
          newVal &&
          (newVal.allow_feedback == 1 || newVal.allow_feedback === true) &&
          this.pageId
        ) {
          this.showFeedback = true
          // 延迟1秒加载，确保页面渲染完成
          this.loadTimer = setTimeout(() => {
            this.loadFeedbackStat()
          }, 1000)
        } else {
          this.showFeedback = false
        }
      }
    }
  },
  methods: {
    // 加载反馈统计
    async loadFeedbackStat() {
      if (!this.pageId) return

      try {
        const res = await getFeedbackStat(this.pageId)
        if (res.error_code === 0 && res.data) {
          this.helpfulCount = res.data.helpful_count || 0
          this.unhelpfulCount = res.data.unhelpful_count || 0
          this.userFeedback = res.data.user_feedback || 0
        }
      } catch (err) {
        console.error('加载反馈统计失败:', err)
      }
    },
    // 提交反馈
    async submitFeedback(feedbackType) {
      if (!this.pageId) return

      // 如果点击的是已选中的按钮，则取消反馈
      if (this.userFeedback === feedbackType) {
        feedbackType = 0 // 取消反馈
      }

      this.submitting = true
      try {
        const res = await submitFeedback({
          page_id: this.pageId,
          feedback_type: feedbackType
        })
        if (res.error_code === 0 && res.data) {
          this.helpfulCount = res.data.helpful_count || 0
          this.unhelpfulCount = res.data.unhelpful_count || 0
          this.userFeedback = res.data.user_feedback || 0
          if (res.data.message) {
            this.$message.success(res.data.message)
          }
        }
      } catch (err) {
        console.error('提交反馈失败:', err)
      } finally {
        this.submitting = false
      }
    }
  },
  beforeDestroy() {
    // 组件销毁前清除定时器
    if (this.loadTimer) {
      clearTimeout(this.loadTimer)
      this.loadTimer = null
    }
  }
}
</script>

<style scoped>
.page-feedback {
  margin: 30px 0;
  padding: 20px;
  border-top: 1px solid #e6e6e6;
  border-bottom: 1px solid #e6e6e6;
}

.feedback-title {
  margin-bottom: 15px;
  font-size: 14px;
  color: #666;
}

.el-button-group {
  display: flex;
  gap: 10px;
}
</style>

