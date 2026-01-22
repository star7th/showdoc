import request from '@/utils/request'

/**
 * 成员相关接口
 */

// 获取项目成员列表
export function getMemberList(itemId: string) {
  return request(
    '/api/member/getList',
    {
      item_id: itemId
    },
    'post',
    true,
    'form'
  )
}

// 添加项目成员
export function saveMember(data: any) {
  return request(
    '/api/member/save',
    data,
    'post',
    false,
    'form'
  )
}

// 删除项目成员
export function deleteMember(itemId: string, itemMemberId: number) {
  return request(
    '/api/member/delete',
    {
      item_id: itemId,
      item_member_id: itemMemberId
    },
    'post',
    true,
    'form'
  )
}

// 获取我之前添加过的成员列表
export function getMyAllList() {
  return request(
    '/api/member/getMyAllList',
    {},
    'post',
    true,
    'form'
  )
}

// 获取一个项目的所有成员列表（包括单独成员和绑定的团队成员）
export function getAllItemMemberList(itemId: string | number) {
  return request(
    '/api/member/getAllList',
    {
      item_id: itemId
    },
    'post',
    true,
    'form'
  )
}

