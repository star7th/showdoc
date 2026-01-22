// 页面评论相关API

import request from '@/utils/request'

interface Comment {
  comment_id: number
  page_id: number
  cat_id: number
  item_id: number
  username: string
  uid: number
  content: string
  parent_id: number
  addtime: number
  addtime_text: string
  can_delete: boolean
  replies?: Comment[]
}

interface CommentListParams {
  page_id: number
  page: number
  count: number
}

interface CommentListResponse {
  comments: Comment[]
  total: number
}

interface AddCommentParams {
  page_id: number
  content: string
  parent_id: number
}

// 获取评论列表
export function getCommentList(params: CommentListParams) {
  return request<{
    error_code: number
    data: CommentListResponse
  }>('/api/page_comment/getList', params, 'post', false)
}

// 发表评论/回复
export function addComment(data: AddCommentParams) {
  return request<{
    error_code: number
    data: { comment_id: number }
    message?: string
  }>('/api/page_comment/add', data, 'post', true)
}

// 删除评论
export function deleteComment(commentId: number) {
  return request<{
    error_code: number
    data: any
  }>('/api/page_comment/delete', { comment_id: commentId }, 'post', true)
}

