import request from '@/utils/request'

export interface AttachmentItem {
  file_id: number | string
  display_name: string
  file_type: string
  file_size_m: string | number
  visit_times: number | string
  addtime: string
  url: string
}

/**
 * 获取我的附件列表
 */
export function getMyAttachmentList(params: {
  page: number
  count: number
  attachment_type?: string
  display_name?: string
  username?: string
}) {
  return request(
    '/api/attachment/getMyList',
    params,
    'post',
    true,
    'form'
  )
}

/**
 * 删除我的附件
 */
export function deleteMyAttachment(params: { file_id: number | string }) {
  return request(
    '/api/attachment/deleteMyAttachment',
    params,
    'post',
    true,
    'form'
  )
}

/**
 * 获取七牛云上传凭证
 */
export function getQiniuUploadToken() {
  return request(
    '/api/attachment/getQiniuUploadToken',
    {},
    'post',
    true,
    'form'
  )
}

/**
 * 七牛云上传回调
 */
export function qiniuUploadCallback(params: {
  key: string
  hash: string
  size?: number
  fname?: string
  mimeType?: string
}) {
  return request(
    '/api/attachment/qiniuUploadCallback',
    params,
    'post',
    true,
    'form'
  )
}

/**
 * 上传文件到服务器（FormData 会自动处理）
 */
export function uploadFile(formData: FormData) {
  return request(
    '/api/page/upload',
    formData,
    'post',
    true
  )
}
