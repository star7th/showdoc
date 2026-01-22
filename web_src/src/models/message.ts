import request from '@/utils/request'

// 消息接口定义
export interface MessageItem {
  id: number
  from_uid: number
  from_name: string
  message_content_id: number
  message_content: string
  addtime: string
  status: number // 0: 未读, 1: 已读
  action_type?: 'create' | 'update' | 'comment' | 'comment_reply'
  object_type?: 'page' | 'vip'
  page_data?: {
    item_id: number
    page_id: number
    page_title: string
  }
}

export interface UnreadMessage {
  remind?: MessageItem
  announce?: MessageItem
}

// 获取提醒列表
export function getRemindList(params: { page: number; count: number }) {
  return request('/api/message/getRemindList', params, 'post')
}

// 获取公告列表
export function getAnnouncementList() {
  return request('/api/message/getAnnouncementList', {}, 'post')
}

// 设置消息已读
export function setRead(params: { from_uid: number; message_content_id: number }) {
  return request('/api/message/setRead', params, 'post')
}

// 获取未读消息
export function getUnread() {
  return request('/api/message/getUnread', {}, 'post', false)
}

