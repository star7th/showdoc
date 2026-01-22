import request from '@/utils/request'

/**
 * 订阅/通知相关接口
 */

// 获取页面订阅列表
export async function getPageList(pageId: string | number) {
  const res = await request(
    '/api/subscription/getPageList',
    {
      page_id: pageId
    },
    'post',
    true,
    'form'
  )
  return (res as any)?.data || []
}

// 保存页面订阅（添加成员）
export async function savePage(page_id: string | number, uids: string | number[]) {
  const uidsStr = Array.isArray(uids) ? uids.join(',') : uids
  return request(
    '/api/subscription/savePage',
    {
      page_id,
      uids: uidsStr
    },
    'post',
    true,
    'form'
  )
}

// 删除页面订阅（删除成员）
export async function deletePage(page_id: string | number, uids: string | number[]) {
  const uidsStr = Array.isArray(uids) ? uids.join(',') : uids
  return request(
    '/api/subscription/deletePage',
    {
      page_id,
      uids: uidsStr
    },
    'post',
    true,
    'form'
  )
}

// 获取项目所有成员列表
export async function getAllItemMemberList(itemId: string | number) {
  const res = await request(
    '/api/member/getAllList',
    {
      item_id: itemId
    },
    'post',
    true,
    'form'
  )
  return (res as any)?.data || []
}
