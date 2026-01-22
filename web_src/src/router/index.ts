import { createRouter, createWebHashHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  // ============ Landing 页面（独立布局）============
  {
    path: '/',
    name: 'Index',
    component: () => import('@/views/landing/Index.vue'),
    meta: { title: 'ShowDoc - 在线API文档、技术文档工具' },
  },

  // ============ 用户模块 ============
  {
    path: '/user/login',
    name: 'Login',
    component: () => import('@/views/user/Login.vue'),
    meta: { title: '登录 - ShowDoc' },
  },
  {
    path: '/user/register',
    name: 'Register',
    component: () => import('@/views/user/Register.vue'),
    meta: { title: '注册 - ShowDoc' },
  },
  {
    path: '/user/resetPassword',
    name: 'ResetPassword',
    component: () => import('@/views/user/ResetPassword.vue'),
    meta: { title: '重置密码 - ShowDoc' },
  },
  {
    path: '/user/resetPasswordByUrl/:token',
    name: 'ResetPasswordByUrl',
    component: () => import('@/views/user/ResetPasswordByUrl.vue'),
    meta: { title: '重置密码 - ShowDoc' },
  },
  {
    path: '/user/verifyEmailByUrl/:token',
    name: 'VerifyEmailByUrl',
    component: () => import('@/views/user/VerifyEmailByUrl.vue'),
    meta: { title: '验证邮箱 - ShowDoc' },
  },
  {
    path: '/user/setting',
    name: 'UserSetting',
    component: () => import('@/views/user/Setting.vue'),
    meta: { title: '个人设置 - ShowDoc', requiresAuth: true },
  },
  {
    path: '/user/center',
    name: 'UserCenter',
    component: () => import('@/views/user/Setting.vue'),
    meta: { title: '个人设置 - ShowDoc', requiresAuth: true },
  },
  {
    path: '/user/loginByUserToken',
    name: 'LoginByUserToken',
    component: () => import('@/views/user/LoginByUserToken.vue'),
    meta: { title: '第三方登录 - ShowDoc' },
  },

  // ============ 项目模块 ============
  {
    path: '/item/index',
    name: 'ItemIndex',
    component: () => import('@/views/item/home/Index.vue'),
    meta: { title: '我的项目 - ShowDoc', requiresAuth: true },
  },
  {
    path: '/item/setting/:item_id',
    name: 'ItemSetting',
    component: () => import('@/views/item/setting/Index.vue'),
    meta: { title: '项目设置 - ShowDoc', requiresAuth: true },
  },
  {
    path: '/item/export/:item_id',
    name: 'ItemExport',
    component: () => import('@/views/item/export/Index.vue'),
    meta: { title: '导出项目 - ShowDoc' },
  },
  {
    path: '/item/password/:item_id',
    name: 'ItemPassword',
    component: () => import('@/views/item/password/Index.vue'),
    meta: { title: '输入访问密码 - ShowDoc' },
  },

  // ============ 页面模块 ============
  {
    path: '/page/edit/:item_id/:page_id',
    name: 'PageEdit',
    component: () => import('@/views/page/edit/Index.vue'),
    meta: { title: '编辑页面 - ShowDoc', requiresAuth: true },
  },
  {
    path: '/page/:page_id',
    name: 'PageShow',
    component: () => import('@/views/page/show/Index.vue'),
    meta: { title: 'ShowDoc' },
  },
  {
    path: '/p/:unique_key',
    name: 'PageUniqueKey',
    component: () => import('@/views/page/show/Index.vue'),
    meta: { title: 'ShowDoc' },
  },
  {
    path: '/page/diff/:page_id/:page_history_id',
    name: 'PageDiff',
    component: () => import('@/views/page/diff/Index.vue'),
    meta: { title: '版本对比 - ShowDoc' },
  },

  // ============ 团队模块 ============
  {
    path: '/team/index',
    name: 'TeamIndex',
    component: () => import('@/views/team/Index.vue'),
    meta: { title: '团队管理 - ShowDoc', requiresAuth: true },
  },
  {
    path: '/team/member/:team_id',
    name: 'TeamMember',
    component: () => import('@/views/team/Member.vue'),
    meta: { title: '团队成员 - ShowDoc', requiresAuth: true },
  },
  {
    path: '/team/item/:team_id',
    name: 'TeamItem',
    component: () => import('@/views/team/Item.vue'),
    meta: { title: '团队项目 - ShowDoc', requiresAuth: true },
  },

  // ============ 消息模块 ============
  {
    path: '/message/index',
    name: 'MessageIndex',
    component: () => import('@/views/message/Index.vue'),
    meta: { title: '我的消息 - ShowDoc' },
  },

  // ============ 附件模块 ============
  {
    path: '/attachment/index',
    name: 'AttachmentIndex',
    component: () => import('@/views/attachment/Index.vue'),
    meta: { title: '附件管理 - ShowDoc', requiresAuth: true },
  },

  // ============ 管理后台 ============
  {
    path: '/admin/index',
    name: 'AdminIndex',
    component: () => import('@/views/admin/Index.vue'),
    meta: { title: '管理后台 - ShowDoc', requiresAuth: true, requiresAdmin: true },
  },

  // ============ 其他公共页面 ============
  {
    path: '/public-square/index',
    name: 'PublicSquare',
    component: () => import('@/views/public-square/Index.vue'),
    meta: { title: '公开广场 - ShowDoc' },
  },

  // ============ 通配符路由（放在最后）============
  // 项目展示页
  {
    path: '/:item_id',
    name: 'ItemShow',
    component: () => import('@/views/item/show/Index.vue'),
    meta: { title: 'ShowDoc' },
  },
  // 项目+页面直达路由
  {
    path: '/:item_id/:page_id(\\d+)',
    name: 'ItemShowPage',
    component: () => import('@/views/item/show/Index.vue'),
    meta: { title: 'ShowDoc' },
  },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
})

// 路由守卫
router.beforeEach((to, _from, next) => {
  // 设置页面标题
  if (to.meta?.title) {
    document.title = to.meta.title as string
  }

  // 登录验证
  if (to.meta?.requiresAuth) {
    const userinfo = JSON.parse(localStorage.getItem('userinfo') || 'null')
    if (!userinfo) {
      return next('/user/login')
    }

    // 管理员权限验证
    if (to.meta?.requiresAdmin && Number(userinfo.groupid) !== 1) {
      console.warn('访问管理后台需要管理员权限')
      // 可以跳转到首页或提示权限不足
      return next('/')
    }
  }

  next()
})

export default router

