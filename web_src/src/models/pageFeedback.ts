// 页面反馈相关API

import request from '@/utils/request'
import { getClientId } from '@/utils/clientId'

interface FeedbackStat {
  helpful_count: number
  unhelpful_count: number
  user_feedback: number // 0=未反馈，1=有帮助，2=没有帮助
  message?: string
}

interface SubmitFeedbackParams {
  page_id: number
  feedback_type: number // 0=取消反馈，1=有帮助，2=没有帮助
}

interface SubmitFeedbackResponse {
  helpful_count: number
  unhelpful_count: number
  user_feedback: number
  message?: string
}

// 获取反馈统计
export function getFeedbackStat(pageId: number) {
  return request<{
    error_code: number
    data: FeedbackStat
  }>(
    '/api/page_feedback/getStat',
    {
      page_id: pageId,
      client_id: getClientId()
    },
    'post',
    false
  )
}

// 提交/修改反馈
export function submitFeedback(data: SubmitFeedbackParams) {
  return request<{
    error_code: number
    data: SubmitFeedbackResponse
  }>(
    '/api/page_feedback/submit',
    {
      ...data,
      client_id: getClientId()
    },
    'post',
    true
  )
}

