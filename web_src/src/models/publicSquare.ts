import request from '@/utils/request'

/**
 * 公开广场相关接口
 */

// 检查公开广场是否启用
export function checkPublicSquareEnabled() {
  return request(
    '/api/publicSquare/checkEnabled',
    {},
    'post',
    true,
    'form'
  )
}

/**
 * 获取公开项目列表
 */
export function getPublicItems(params: {
  page: number
  count: number
  keyword?: string
  search_type?: string
}) {
  return request(
    '/api/publicSquare/getPublicItems',
    params,
    'post',
    true,
    'form'
  )
}
