<template>
  <div class="page-comments" v-if="showComments">
    <div class="comments-header">
      <span class="comments-title">{{ $t('pageComment.title') }}</span>
      <span class="comments-count" v-if="total > 0">
        {{ $t('pageComment.total', { count: total }) }}
      </span>
    </div>

    <!-- 评论列表 -->
    <div class="comments-list" v-if="comments.length > 0">
      <div
        class="comment-item"
        v-for="comment in comments"
        :key="comment.comment_id"
        :id="'comment-' + comment.comment_id"
      >
        <!-- 一级评论 -->
        <div class="comment-main">
          <div class="comment-header">
            <span class="username">👤 {{ comment.username }}</span>
            <span class="time">{{ comment.addtime_text }}</span>
          </div>
          <div class="comment-content" v-text="comment.content"></div>
          <div class="comment-actions">
            <CommonButton
              v-if="isLogin"
              type="text"
              size="small"
              @click="showReplyForm(comment.comment_id, comment.username)"
            >
              {{ $t('pageComment.reply') }}
            </CommonButton>
            <CommonButton
              v-if="comment.can_delete"
              type="text"
              size="small"
              @click="deleteComment(comment.comment_id)"
            >
              {{ $t('pageComment.delete') }}
            </CommonButton>
          </div>
        </div>

        <!-- 回复列表 -->
        <div
          class="comment-replies"
          v-if="comment.replies && comment.replies.length > 0"
        >
          <div
            class="reply-item"
            v-for="reply in comment.replies"
            :key="reply.comment_id"
          >
            <div class="reply-header">
              <span class="username">↪ {{ reply.username }}</span>
              <span class="time">{{ reply.addtime_text }}</span>
              <CommonButton
                v-if="reply.can_delete"
                type="text"
                size="small"
                @click="deleteComment(reply.comment_id)"
              >
                {{ $t('pageComment.delete') }}
              </CommonButton>
            </div>
            <div class="reply-content" v-text="reply.content"></div>
          </div>
        </div>

        <!-- 回复输入框 -->
        <div class="reply-form" v-if="replyingTo === comment.comment_id">
          <CommonTextarea
            v-model="replyContent"
            :rows="2"
            :maxlength="500"
            :placeholder="
              $t('pageComment.replyPlaceholder', {
                username: '@' + replyingToUsername
              })
            "
          />
          <div class="reply-form-actions">
            <CommonButton size="small" @click="cancelReply">
              {{ $t('pageComment.cancel') }}
            </CommonButton>
            <CommonButton
              type="primary"
              size="small"
              :loading="submitting"
              @click="submitReply(comment.comment_id)"
            >
              {{ $t('pageComment.submitReply') }}
            </CommonButton>
          </div>
        </div>
      </div>
    </div>

    <!-- 空状态 -->
    <div class="comments-empty" v-else-if="!loading">
      {{ $t('pageComment.empty') }}
    </div>

    <!-- 分页 -->
    <div class="comments-pagination" v-if="total > count">
      <a-pagination
        :current="currentPage"
        :page-size="count"
        :total="total"
        @change="handlePageChange"
        small
      />
    </div>

    <!-- 发表评论 -->
    <div class="comment-form" v-if="isLogin">
      <CommonTextarea
        v-model="commentContent"
        :rows="3"
        :maxlength="500"
        :placeholder="$t('pageComment.placeholder')"
      />
      <div class="comment-form-actions">
        <CommonButton
          type="primary"
          size="small"
          :loading="submitting"
          @click="submitComment"
        >
          {{ $t('pageComment.submit') }}
        </CommonButton>
      </div>
    </div>
    <div class="comment-login-tip" v-else>
      <span>{{ $t('pageComment.loginTip') }}</span>
      <CommonButton type="text" @click="goLogin">
        {{ $t('pageComment.loginButton') }}
      </CommonButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '@/store/user'
import { getCommentList, addComment, deleteComment as deleteCommentApi } from '@/models/pageComment'
import ConfirmModal from '@/components/ConfirmModal'
import Message from '@/components/Message'
import CommonButton from '@/components/CommonButton.vue'
import CommonTextarea from '@/components/CommonTextarea.vue'

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
const router = useRouter()
const { t } = useI18n()
const userStore = useUserStore()

// Refs
const comments = ref<any[]>([])
const total = ref(0)
const currentPage = ref(1)
const count = 20
const loading = ref(false)
const submitting = ref(false)
const commentContent = ref('')
const replyingTo = ref(0)
const replyingToUsername = ref('')
const replyContent = ref('')
const isLogin = ref(false)
const showComments = ref(false)
let loadTimer: number | null = null

// Methods
const checkLogin = () => {
  isLogin.value = userStore.isLoggedIn
}

const loadComments = async () => {
  if (!props.pageId) return

  loading.value = true
  try {
    const res = await getCommentList({
      page_id: props.pageId,
      page: currentPage.value,
      count: count
    })
    if (res.error_code === 0 && res.data) {
      comments.value = res.data.comments || []
      total.value = res.data.total || 0
    }
  } catch (err) {
    console.error('加载评论失败:', err)
  } finally {
    loading.value = false
  }
}

const submitComment = async () => {
  if (!commentContent.value.trim()) {
    Message.warning(t('pageComment.placeholder'))
    return
  }

  submitting.value = true
  try {
    const res = await addComment({
      page_id: props.pageId,
      content: commentContent.value.trim(),
      parent_id: 0
    })
    if (res.error_code === 0) {
      commentContent.value = ''
      Message.success(t('pageComment.success'))
      // 重新加载评论列表
      currentPage.value = 1
      await loadComments()
      // 滚动到顶部
      setTimeout(() => {
        const commentArea = document.querySelector('.page-comments')
        if (commentArea) {
          commentArea.scrollIntoView({ behavior: 'smooth', block: 'start' })
        }
      }, 0)
    }
  } catch (err) {
    console.error('发表评论失败:', err)
  } finally {
    submitting.value = false
  }
}

const showReplyForm = (commentId: number, username: string) => {
  replyingTo.value = commentId
  replyingToUsername.value = username
  replyContent.value = ''
}

const cancelReply = () => {
  replyingTo.value = 0
  replyingToUsername.value = ''
  replyContent.value = ''
}

const submitReply = async (parentId: number) => {
  if (!replyContent.value.trim()) {
    Message.warning(t('pageComment.replyPlaceholder'))
    return
  }

  submitting.value = true
  try {
    const res = await addComment({
      page_id: props.pageId,
      content: replyContent.value.trim(),
      parent_id: parentId
    })
    if (res.error_code === 0) {
      cancelReply()
      Message.success(t('pageComment.replySuccess'))
      // 重新加载评论列表
      await loadComments()
    }
  } catch (err) {
    console.error('发表回复失败:', err)
  } finally {
    submitting.value = false
  }
}

const deleteComment = async (commentId: number) => {
  try {
    const result = await ConfirmModal({
      title: t('tips'),
      content: t('pageComment.deleteConfirm'),
      confirmText: t('common.confirm'),
      cancelText: t('common.cancel')
    })

    if (result) {
      const res = await deleteCommentApi(commentId)
      if (res.error_code === 0) {
        Message.success(t('pageComment.delete') + t('common.success'))
        // 重新加载评论列表
        await loadComments()
      }
    }
  } catch (err) {
    if (err !== 'cancel') {
      console.error('删除评论失败:', err)
    }
  }
}

const handlePageChange = (page: number) => {
  currentPage.value = page
  loadComments()
  // 滚动到评论区域顶部
  setTimeout(() => {
    const commentArea = document.querySelector('.page-comments')
    if (commentArea) {
      commentArea.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }, 0)
}

const goLogin = () => {
  const redirect = router.currentRoute.value.fullPath
  router.push({
    path: '/user/login',
    query: { redirect }
  })
}

const checkAllowComment = () => {
  if (loadTimer) {
    clearTimeout(loadTimer)
    loadTimer = null
  }

  if (props.pageId && props.itemInfo) {
    // 检查是否开启评论功能（使用弱等于，因为后端可能返回字符串）
    const allow = props.itemInfo.allow_comment == 1 || props.itemInfo.allow_comment === true
    if (allow) {
      showComments.value = true
      // 延迟加载，确保页面渲染完成
      loadTimer = window.setTimeout(() => {
        loadComments()
      }, 1000)
    } else {
      showComments.value = false
    }
  } else {
    showComments.value = false
  }
}

// Watchers
import { watch } from 'vue'
watch(() => props.pageId, checkAllowComment, { immediate: true })
watch(
  () => props.itemInfo,
  checkAllowComment,
  { deep: true, immediate: true }
)

// Lifecycle
onMounted(() => {
  checkLogin()
})

onBeforeUnmount(() => {
  if (loadTimer) {
    clearTimeout(loadTimer)
    loadTimer = null
  }
})
</script>

<style scoped lang="scss">
.page-comments {
  margin: 30px 0;
  padding: 20px;
  border-top: 1px solid var(--color-border);
}

.comments-header {
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--color-border);
}

.comments-title {
  font-size: 16px;
  font-weight: bold;
  margin-right: 10px;
  color: var(--color-text-primary);
}

.comments-count {
  font-size: 14px;
  color: var(--color-text-secondary);
}

.comments-list {
  margin-bottom: 20px;
}

.comment-item {
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--color-border);
}

.comment-item:last-child {
  border-bottom: none;
}

.comment-main {
  margin-bottom: 10px;
}

.comment-header {
  margin-bottom: 8px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.username {
  font-weight: 500;
  color: var(--color-text-primary);
}

.time {
  font-size: 12px;
  color: var(--color-text-secondary);
}

.comment-content {
  margin-bottom: 8px;
  line-height: 1.6;
  color: var(--color-text-secondary);
  white-space: pre-wrap;
  word-break: break-word;
}

.comment-actions {
  display: flex;
  gap: 10px;
}

.comment-replies {
  margin-left: 30px;
  margin-top: 10px;
  padding-left: 15px;
  border-left: 2px solid var(--color-border);
}

.reply-item {
  margin-bottom: 10px;
}

.reply-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 5px;
}

.reply-content {
  line-height: 1.6;
  color: var(--color-text-secondary);
  white-space: pre-wrap;
  word-break: break-word;
}

.reply-form {
  margin-top: 10px;
  margin-left: 30px;
}

.reply-form-actions {
  margin-top: 10px;
  display: flex;
  gap: 10px;
}

.comments-empty {
  text-align: center;
  padding: 40px 0;
  color: var(--color-text-secondary);
}

.comments-pagination {
  margin: 20px 0;
  text-align: center;
}

.comment-form {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid var(--color-border);
}

.comment-form-actions {
  margin-top: 10px;
  display: flex;
  justify-content: flex-end;
}

.comment-login-tip {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid var(--color-border);
  text-align: center;
  color: var(--color-text-secondary);
}
</style>
