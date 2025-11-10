// 页面评论相关API

import request from '@/request.js'

// 获取评论列表
export function getCommentList(params) {
  return request('/api/page_comment/getList', params, 'post', false)
}

// 发表评论/回复
export function addComment(data) {
  return request('/api/page_comment/add', data, 'post', true)
}

// 删除评论
export function deleteComment(comment_id) {
  return request('/api/page_comment/delete', { comment_id }, 'post', true)
}

