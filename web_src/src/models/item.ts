import request from '@/utils/request'

/**
 * 项目相关接口
 */

// 获取我的项目列表
export function getMyList(itemGroupId: number = 0) {
  return request(
    '/api/item/myList',
    {
      item_group_id: itemGroupId
    },
    'post',
    true,
    'form'
  )
}

// 获取项目列表（不使用 group）
export function getItemList(params: any) {
  return request(
    '/api/item/getList',
    params,
    'post',
    true,
    'form'
  )
}

// 获取单个项目详情
export function getItem(itemId: string) {
  return request(
    '/api/item/info',
    {
      item_id: itemId
    },
    'post',
    true,
    'form'
  )
}

// 获取项目详情(用于编辑)
export function getItemDetail(itemId: string) {
  return request(
    '/api/item/detail',
    {
      item_id: itemId
    },
    'post',
    true,
    'form'
  )
}

// 更新项目信息
export function updateItem(itemId: string, data: any, msgAlert = true) {
  return request(
    '/api/item/update',
    {
      item_id: itemId,
      ...data
    },
    'post',
    msgAlert,
    'form'
  )
}

// 删除项目
export function deleteItem(itemId: string, password: string) {
  return request(
    '/api/item/delete',
    {
      item_id: itemId,
      password: password
    },
    'post',
    true,
    'form'
  )
}

// 归档项目
export function archiveItem(itemId: string, password: string) {
  return request(
    '/api/item/archive',
    {
      item_id: itemId,
      password: password
    },
    'post',
    true,
    'form'
  )
}

// 转让项目
export function attornItem(itemId: string, username: string, password: string) {
  return request(
    '/api/item/attorn',
    {
      item_id: itemId,
      username: username,
      password: password
    },
    'post',
    true,
    'form'
  )
}

// 退出项目
export function exitItem(itemId: string) {
  return request(
    '/api/item/exitItem',
    {
      item_id: itemId
    },
    'post',
    true,
    'form'
  )
}

// 标记星标
export function starItem(itemId: string) {
  return request(
    '/api/item/star',
    {
      item_id: itemId
    },
    'post',
    true,
    'form'
  )
}

// 取消星标
export function unstarItem(itemId: string) {
  return request(
    '/api/item/unstar',
    {
      item_id: itemId
    },
    'post',
    true,
    'form'
  )
}

// 项目排序
export function sortItem(data: Record<string, number>, itemGroupId: number = 0) {
  return request(
    '/api/item/sort',
    {
      data: JSON.stringify(data),
      item_group_id: itemGroupId
    },
    'post',
    true,
    'form'
  )
}

// 搜索项目
export function searchItem(keyword: string, itemId: number) {
  return request(
    '/api/item/search',
    {
      keyword: keyword,
      item_id: itemId
    },
    'post',
    true,
    'form'
  )
}

// 添加项目（支持复制）
export function addItem(data: {
  copy_item_id?: number
  item_name: string
  item_description?: string
  password?: string
  item_type?: number
}, msgAlert = true) {
  return request(
    '/api/item/add',
    data,
    'post',
    msgAlert,
    'form'
  )
}

// 复制项目
export function copyItem(itemId: string, targetGroupId?: number) {
  return request(
    '/api/item/copy',
    {
      item_id: itemId,
      item_group_id: targetGroupId
    },
    'post',
    true,
    'form'
  )
}

// 获取项目密码验证状态
export function getItemPassword(itemId: string) {
  return request(
    '/api/item/checkPassword',
    {
      item_id: itemId
    },
    'post',
    true,
    'form'
  )
}

// 验证项目密码
export function verifyItemPassword(itemId: string, password: string) {
  return request(
    '/api/item/password',
    {
      item_id: itemId,
      password: password
    },
    'post',
    true,
    'form'
  )
}

// 项目导出
export function exportItem(itemId: string, data: any) {
  return request(
    '/api/item/export',
    {
      item_id: itemId,
      ...data
    },
    'post',
    true,
    'form'
  )
}

// 获取项目的开放API密钥
export function getItemApiKey(itemId: string) {
  return request(
    '/api/item/getKey',
    {
      item_id: itemId
    },
    'post',
    true,
    'form'
  )
}

// 重置项目的开放API密钥
export function resetItemApiKey(itemId: string) {
  return request(
    '/api/item/resetKey',
    {
      item_id: itemId
    },
    'post',
    true,
    'form'
  )
}