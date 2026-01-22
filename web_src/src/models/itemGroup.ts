import request from '@/utils/request'

/**
 * 项目分组相关接口
 */

// 获取分组列表
export function getGroupList() {
  return request(
    '/api/itemGroup/getList',
    {},
    'post',
    true,
    'form'
  )
}

// 添加分组
export function addGroup(groupName: string) {
  return request(
    '/api/itemGroup/add',
    {
      group_name: groupName
    },
    'post',
    true,
    'form'
  )
}

// 更新分组
export function updateGroup(groupId: number, groupName: string) {
  return request(
    '/api/itemGroup/update',
    {
      id: groupId,
      group_name: groupName
    },
    'post',
    true,
    'form'
  )
}

// 删除分组
export function deleteGroup(params: { id: string | number }) {
  return request(
    '/api/itemGroup/delete',
    params,
    'post',
    true,
    'form'
  )
}

// 保存分组（新增/编辑）
export function saveGroup(params: {
  group_name: string
  id?: string
  item_ids?: string
}) {
  return request(
    '/api/itemGroup/save',
    params,
    'post',
    true,
    'form'
  )
}

// 保存分组排序
export function saveGroupSort(params: { groups: string }) {
  return request(
    '/api/itemGroup/saveSort',
    params,
    'post',
    true,
    'form'
  )
}
