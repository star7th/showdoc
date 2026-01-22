import request from '@/utils/request'

/**
 * 团队相关接口
 */

// 获取团队列表
export function getTeamList() {
  return request(
    '/api/team/getList',
    {},
    'post',
    true,
    'form'
  )
}

// 添加/编辑团队
export function saveTeam(data: any) {
  return request(
    '/api/team/save',
    data,
    'post',
    true,
    'form'
  )
}

// 删除团队
export function deleteTeam(teamId: number) {
  return request(
    '/api/team/delete',
    {
      id: teamId
    },
    'post',
    true,
    'form'
  )
}

// 转让团队
export function attornTeam(teamId: number, username: string, password: string) {
  return request(
    '/api/team/attorn',
    {
      team_id: teamId,
      username: username,
      password: password
    },
    'post',
    true,
    'form'
  )
}

// 退出团队
export function exitTeam(teamId: number) {
  return request(
    '/api/team/exitTeam',
    {
      id: teamId
    },
    'post',
    true,
    'form'
  )
}

// 获取团队项目列表
export function getTeamItemList(itemId: string) {
  return request(
    '/api/teamItem/getList',
    {
      item_id: itemId
    },
    'post',
    true,
    'form'
  )
}

// 添加团队项目
export function saveTeamItem(itemId: string, teamId: string) {
  return request(
    '/api/teamItem/save',
    {
      item_id: itemId,
      team_id: teamId
    },
    'post',
    true,
    'form'
  )
}

// 删除团队项目
export function deleteTeamItem(id: number) {
  return request(
    '/api/teamItem/delete',
    {
      id: id
    },
    'post',
    true,
    'form'
  )
}

// 获取团队成员
export function getTeamItemMember(itemId: string, teamId: number) {
  return request(
    '/api/teamItemMember/getList',
    {
      item_id: itemId,
      team_id: teamId
    },
    'post',
    true,
    'form'
  )
}

// 保存团队成员权限
export function saveTeamItemMember(data: any) {
  return request(
    '/api/teamItemMember/save',
    data,
    'post',
    true,
    'form'
  )
}

// 获取目录列表
export function getCatalogList(itemId: string) {
  return request(
    '/api/catalog/catListGroup',
    {
      item_id: itemId
    },
    'post',
    true,
    'form'
  )
}

// 获取团队成员
export function getTeamMemberList(teamId: number) {
  return request(
    '/api/teamMember/getList',
    {
      team_id: teamId
    },
    'post',
    true,
    'form'
  )
}

// 添加团队成员
export function saveTeamMember(data: any) {
  return request(
    '/api/teamMember/save',
    data,
    'post',
    false,
    'form'
  )
}

// 删除团队成员
export function deleteTeamMember(id: number) {
  return request(
    '/api/teamMember/delete',
    {
      id: id
    },
    'post',
    true,
    'form'
  )
}

// 获取团队项目列表(按团队)
export function getTeamItemListByTeam(teamId: number) {
  return request(
    '/api/teamItem/getListByTeam',
    {
      team_id: teamId
    },
    'post',
    true,
    'form'
  )
}

