<template>
  <div class="page-comments" v-if="showComments" id="comment-area">
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
            <el-button
              v-if="isLogin"
              type="text"
              size="mini"
              @click="showReplyForm(comment.comment_id, comment.username)"
            >
              {{ $t('pageComment.reply') }}
            </el-button>
            <el-button
              v-if="comment.can_delete"
              type="text"
              size="mini"
              @click="deleteComment(comment.comment_id)"
            >
              {{ $t('pageComment.delete') }}
            </el-button>
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
              <el-button
                v-if="reply.can_delete"
                type="text"
                size="mini"
                @click="deleteComment(reply.comment_id)"
              >
                {{ $t('pageComment.delete') }}
              </el-button>
            </div>
            <div class="reply-content" v-text="reply.content"></div>
          </div>
        </div>

        <!-- 回复输入框 -->
        <div class="reply-form" v-if="replyingTo === comment.comment_id">
          <el-input
            type="textarea"
            v-model="replyContent"
            :rows="2"
            :maxlength="500"
            show-word-limit
            :placeholder="
              $t('pageComment.replyPlaceholder', {
                username: replyingToUsername
              })
            "
          >
          </el-input>
          <div class="reply-form-actions">
            <el-button size="small" @click="cancelReply">
              {{ $t('pageComment.cancel') }}
            </el-button>
            <el-button
              type="primary"
              size="small"
              :loading="submitting"
              @click="submitReply(comment.comment_id)"
            >
              {{ $t('pageComment.submitReply') }}
            </el-button>
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
      <el-pagination
        @current-change="handlePageChange"
        :current-page="currentPage"
        :page-size="count"
        :total="total"
        layout="prev, pager, next"
        small
      >
      </el-pagination>
    </div>

    <!-- 发表评论 -->
    <div class="comment-form" v-if="isLogin">
      <el-input
        type="textarea"
        v-model="commentContent"
        :rows="3"
        :maxlength="500"
        show-word-limit
        :placeholder="$t('pageComment.placeholder')"
      >
      </el-input>
      <div class="comment-form-actions">
        <el-button
          type="primary"
          size="small"
          :loading="submitting"
          @click="submitComment"
        >
          {{ $t('pageComment.submit') }}
        </el-button>
      </div>
    </div>
    <div class="comment-login-tip" v-else>
      <span>{{ $t('pageComment.loginTip') }}</span>
      <el-button type="text" @click="goLogin">
        {{ $t('pageComment.loginButton') }}
      </el-button>
    </div>
  </div>
</template>

<script>
import { getCommentList, addComment, deleteComment } from '@/models/pageComment'
import { getUserInfoFromStorage } from '@/models/user'

export default {
  name: 'PageComment',
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
      comments: [],
      total: 0,
      currentPage: 1,
      count: 20,
      loading: false,
      submitting: false,
      commentContent: '',
      replyingTo: 0,
      replyingToUsername: '',
      replyContent: '',
      isLogin: false,
      showComments: false,
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
          (this.itemInfo.allow_comment == 1 ||
            this.itemInfo.allow_comment === true)
        ) {
          this.showComments = true
          // 延迟1秒加载，确保页面渲染完成
          this.loadTimer = setTimeout(() => {
            this.loadComments()
          }, 1000)
        } else {
          this.showComments = false
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
          (newVal.allow_comment == 1 || newVal.allow_comment === true) &&
          this.pageId
        ) {
          this.showComments = true
          // 延迟1秒加载，确保页面渲染完成
          this.loadTimer = setTimeout(() => {
            this.loadComments()
          }, 1000)
        } else {
          this.showComments = false
        }
      }
    }
  },
  mounted() {
    this.checkLogin()
  },
  beforeDestroy() {
    // 组件销毁前清除定时器
    if (this.loadTimer) {
      clearTimeout(this.loadTimer)
      this.loadTimer = null
    }
  },
  methods: {
    // 检查登录状态
    checkLogin() {
      const userinfo = getUserInfoFromStorage()
      this.isLogin = !!userinfo
    },
    // 加载评论列表
    async loadComments() {
      if (!this.pageId) return

      this.loading = true
      try {
        const res = await getCommentList({
          page_id: this.pageId,
          page: this.currentPage,
          count: this.count
        })
        if (res.error_code === 0 && res.data) {
          this.comments = res.data.comments || []
          this.total = res.data.total || 0
        }
      } catch (err) {
        console.error('加载评论失败:', err)
      } finally {
        this.loading = false
      }
    },
    // 发表评论
    async submitComment() {
      if (!this.commentContent.trim()) {
        this.$message.warning(this.$t('pageComment.placeholder'))
        return
      }

      this.submitting = true
      try {
        const res = await addComment({
          page_id: this.pageId,
          content: this.commentContent.trim(),
          parent_id: 0
        })
        if (res.error_code === 0) {
          this.commentContent = ''
          this.$message.success(this.$t('pageComment.success'))
          // 重新加载评论列表
          this.currentPage = 1
          await this.loadComments()
          // 滚动到顶部
          this.$nextTick(() => {
            const commentArea = document.querySelector('.page-comments')
            if (commentArea) {
              commentArea.scrollIntoView({ behavior: 'smooth', block: 'start' })
            }
          })
        }
      } catch (err) {
        console.error('发表评论失败:', err)
      } finally {
        this.submitting = false
      }
    },
    // 显示回复表单
    showReplyForm(commentId, username) {
      this.replyingTo = commentId
      this.replyingToUsername = username
      this.replyContent = ''
    },
    // 取消回复
    cancelReply() {
      this.replyingTo = 0
      this.replyingToUsername = ''
      this.replyContent = ''
    },
    // 提交回复
    async submitReply(parentId) {
      if (!this.replyContent.trim()) {
        this.$message.warning(this.$t('pageComment.replyPlaceholder'))
        return
      }

      this.submitting = true
      try {
        const res = await addComment({
          page_id: this.pageId,
          content: this.replyContent.trim(),
          parent_id: parentId
        })
        if (res.error_code === 0) {
          this.cancelReply()
          this.$message.success(this.$t('pageComment.replySuccess'))
          // 重新加载评论列表
          await this.loadComments()
        }
      } catch (err) {
        console.error('发表回复失败:', err)
      } finally {
        this.submitting = false
      }
    },
    // 删除评论
    async deleteComment(commentId) {
      try {
        await this.$confirm(
          this.$t('pageComment.deleteConfirm'),
          this.$t('tips'),
          {
            confirmButtonText: this.$t('confirm'),
            cancelButtonText: this.$t('cancel'),
            type: 'warning'
          }
        )

        const res = await deleteComment(commentId)
        if (res.error_code === 0) {
          this.$message.success(
            this.$t('pageComment.delete') + this.$t('success')
          )
          // 重新加载评论列表
          await this.loadComments()
        }
      } catch (err) {
        if (err !== 'cancel') {
          console.error('删除评论失败:', err)
        }
      }
    },
    // 分页切换
    handlePageChange(page) {
      this.currentPage = page
      this.loadComments()
      // 滚动到评论区域顶部
      this.$nextTick(() => {
        const commentArea = document.querySelector('.page-comments')
        if (commentArea) {
          commentArea.scrollIntoView({ behavior: 'smooth', block: 'start' })
        }
      })
    },
    // 跳转登录
    goLogin() {
      const redirect = this.$route.fullPath
      this.$router.push({
        path: '/user/login',
        query: { redirect }
      })
    }
  }
}
</script>

<style scoped>
.page-comments {
  margin: 30px 0;
  padding: 20px;
  border-top: 1px solid #e6e6e6;
}

.comments-header {
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid #e6e6e6;
}

.comments-title {
  font-size: 16px;
  font-weight: bold;
  margin-right: 10px;
}

.comments-count {
  font-size: 14px;
  color: #999;
}

.comments-list {
  margin-bottom: 20px;
}

.comment-item {
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid #f0f0f0;
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
  color: #333;
}

.time {
  font-size: 12px;
  color: #999;
}

.comment-content {
  margin-bottom: 8px;
  line-height: 1.6;
  color: #666;
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
  border-left: 2px solid #e6e6e6;
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
  color: #666;
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
  color: #999;
}

.comments-pagination {
  margin: 20px 0;
  text-align: center;
}

.comment-form {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e6e6e6;
}

.comment-form-actions {
  margin-top: 10px;
  display: flex;
  justify-content: flex-end;
}

.comment-login-tip {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e6e6e6;
  text-align: center;
  color: #999;
}
</style>

