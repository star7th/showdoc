import request from '@/utils/request'

/**
 * 获取回收站列表
 */
export const getRecycleList = (item_id: string) => {
  return request('/api/recycle/getList', { item_id })
}

/**
 * 恢复页面
 */
export const recoverPage = (item_id: string, page_id: string) => {
  return request('/api/recycle/recover', {
    item_id: item_id,
    page_id: page_id
  })
}

