// 页面反馈相关API

import request from '@/request.js'
import { getClientId } from '@/utils/clientId'

// 获取反馈统计
export function getFeedbackStat(page_id) {
  return request(
    '/api/page_feedback/getStat',
    {
      page_id,
      client_id: getClientId()
    },
    'post',
    false
  )
}

// 提交/修改反馈
export function submitFeedback(data) {
  return request(
    '/api/page_feedback/submit',
    {
      ...data,
      client_id: getClientId()
    },
    'post',
    true
  )
}

