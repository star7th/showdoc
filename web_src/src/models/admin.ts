/**
 * 管理员相关接口
 */
import request from '@/utils/request'
import { getServerHost } from '@/utils/system'

// ==================== 用户管理 ====================

/**
 * 获取用户列表
 */
export const getUserList = (params: {
  username: string
  page: number
  count: number
}) => {
  return request('/api/adminUser/getList', params)
}

/**
 * 新增/编辑用户
 */
export const saveUser = (params: {
  username: string
  password?: string
  email: string
  email_verify: string
  updateUid?: string
}) => {
  return request('/api/adminUser/addUser', params)
}

/**
 * 删除用户
 */
export const deleteUser = (params: { uid: number }) => {
  return request('/api/adminUser/deleteUser', params)
}

/**
 * 拉黑用户
 */
export const banUser = (params: { uid: number; remark: string }) => {
  return request('/api/adminUser/ban', params)
}

/**
 * 获取用户登录日志
 */
export const getUserLoginLog = (params: { uid: number }) => {
  return request('/api/adminUser/loginLog', params)
}

// ==================== 项目管理 ====================

/**
 * 获取项目列表
 */
export const getAdminItemList = (params: {
  item_name: string
  username: string
  page: number
  count: number
  positive_type: string
  item_type: string
  privacy_type: string
  is_del: string
}) => {
  return request('/api/adminItem/getList', params)
}

/**
 * 删除项目
 */
export const deleteItem = (params: { item_id: number }) => {
  return request('/api/adminItem/deleteItem', params)
}

/**
 * 恢复已删除项目
 */
export const recoverItem = (params: { item_id: number }) => {
  return request('/api/adminItem/recoverItem', params)
}

/**
 * 永久删除项目
 */
export const hardDeleteItem = (params: { item_id: number }) => {
  return request('/api/adminItem/hardDeleteItem', params)
}

/**
 * 转让项目
 */
export const attornItem = (params: { item_id: number; username: string }) => {
  return request('/api/adminItem/attorn', params)
}

/**
 * 拉黑项目
 */
export const banItem = (params: {
  item_id: number
  remark: string
  allow_paid_access: number
  forbid_visitor: number
  forbid_all: number
}) => {
  return request('/api/adminItem/ban', params)
}

/**
 * 放入白名单
 */
export const whiteItem = (params: { item_id: string | number; remark: string }) => {
  return request('/api/adminItem/white', {
    item_id: String(params.item_id),
    remark: params.remark
  })
}

/**
 * 推荐到首页
 */
export const recommendItem = (params: { item_id: string | number }) => {
  return request('/api/adminItem/recommendItem', {
    item_id: String(params.item_id)
  })
}

/**
 * 添加过滤关键词
 */
export const addKeyword = (params: {
  keyword: string
  is_replace: number
  paid_skip: number
}) => {
  return request('/api/adminItem/addKeyword', params)
}

// ==================== 访问管理 ====================

/**
 * 获取访问量列表
 */
export const getVisitList = (params: {
  page: number
  count: number
  number_type: string
  day_num: string
  item_type: string
}) => {
  return request('/api/adminItem/getVisitList', params)
}

// ==================== 首页推荐 ====================

/**
 * 获取推荐项目列表
 */
export const getRecommendList = (params?: { page: number; count: number }) => {
  return request('/api/item/getRecommend', params || { page: 1, count: 1000000 })
}

/**
 * 删除推荐项目
 */
export const deleteRecommendItem = (params: { item_id: number }) => {
  return request('/api/adminItem/deleteRecommend', params)
}

// ==================== 系统公告 ====================

/**
 * 添加系统公告
 */
export const addAnnouncement = (params: {
  message_type: string
  message_content: string
  send_at?: string
}) => {
  return request('/api/adminMessage/addAnnouncement', params)
}

/**
 * 获取公告列表
 */
export const getAnnouncementList = (params: {
  page: number
  count: number
}) => {
  return request('/api/adminMessage/listAnnouncements', params)
}

// ==================== 系统设置 ====================

/**
 * 加载系统配置
 */
export const loadSystemConfig = (params?: any) => {
  return request('/api/adminSetting/loadConfig', params || {})
}

/**
 * 保存系统配置
 * 注意：布尔类型字段在后端会被存储为数字(1/0)或字符串('1'/'0')
 * 这里使用 boolean 类型，axios 会自动处理序列化
 */
export const saveSystemConfig = (params: {
  register_open: boolean | string | number
  site_url: string
  home_page: string
  home_item: string
  open_api_key: string
  open_api_host: string
  ai_model_name: string
  ai_service_url: string
  ai_service_token: string
  force_login: boolean | string | number
  enable_public_square: boolean | string | number
  strong_password_enabled: boolean | string | number
  session_expire_days: number
  history_version_count: number
  show_watermark: boolean | string | number
  beian: string
  // OSS 配置
  oss_open: boolean | string | number
  oss_setting: {
    oss_type: string
    key?: string
    secret?: string
    endpoint?: string
    region?: string
    secretId?: string
    secretKey?: string
    bucket: string
    subcat?: string
    protocol: string
    domain?: string
  }
}) => {
  return request(
    '/api/adminSetting/saveConfig',
    params,
    'post',
    true,
    'json'
  )
}

/**
 * 测试AI服务连接
 */
export const testAiService = (params: {
  ai_service_url: string
  ai_service_token: string
}) => {
  return request('/api/adminSetting/testAiService', params)
}

// ==================== 附件管理 ====================

/**
 * 获取所有附件列表
 */
export const getAllAttachments = (params: {
  page: number
  count: number
  attachment_type?: string
  display_name?: string
  username?: string
}) => {
  return request('/api/attachment/getAllList', params)
}

/**
 * 批量转让附件
 */
export const transferAttachmentsByAdmin = (params: {
  target_username: string
  file_ids: string
}) => {
  return request('/api/attachment/transferAttachmentsByAdmin', params)
}

/**
 * 删除附件
 */
export const deleteAttachment = (params: { file_id: number }) => {
  return request('/api/attachment/deleteAttachment', params)
}

/**
 * 获取未使用附件列表
 */
export const getUnusedAttachments = (params: {
  page: number
  count: number
  display_name?: string
  username?: string
}) => {
  return request('/api/attachment/getUnusedList', params)
}

/**
 * 批量删除附件
 */
export const batchDeleteAttachments = (params: { file_ids: string }) => {
  return request('/api/attachment/batchDeleteAttachments', params, 'post', true, 'json')
}

// ==================== 扩展登录配置 ====================

/**
 * 加载LDAP配置
 */
export const loadLdapConfig = () => {
  return request('/api/adminSetting/loadLdapConfig')
}

/**
 * 保存LDAP配置
 */
export const saveLdapConfig = (params: {
  ldap_open: boolean | string | number
  ldap_form: {
    host: string
    port: string
    version: string
    base_dn: string
    bind_dn: string
    bind_password: string
    user_field: string
    name_field: string
    search_filter: string
  }
}) => {
  return request(
    '/api/adminSetting/saveLdapConfig',
    params,
    'post',
    true,
    'json'
  )
}

/**
 * 同步LDAP用户
 */
export const syncLdapUsers = () => {
  return request('/api/adminSetting/syncLdapUsers')
}

/**
 * 加载OAuth2配置
 */
export const loadOauth2Config = () => {
  return request('/api/adminSetting/loadOauth2Config')
}

/**
 * 保存OAuth2配置
 */
export const saveOauth2Config = (params: {
  oauth2_open: boolean | string | number
  oauth2_form: {
    redirectUri: string
    entrance_tips: string
    client_id: string
    client_secret: string
    protocol: string
    host: string
    authorize_path: string
    token_path: string
    resource_path: string
    userinfo_path: string
    logout_redirect_uri: string
  }
}) => {
  return request(
    '/api/adminSetting/saveOauth2Config',
    params,
    'post',
    true,
    'json'
  )
}

/**
 * 获取通用接入密钥
 */
export const getLoginSecretKey = () => {
  return request('/api/adminSetting/getLoginSecretKey')
}

/**
 * 重置通用接入密钥
 */
export const resetLoginSecretKey = () => {
  return request('/api/adminSetting/resetLoginSecretKey')
}
