import Vue from 'vue'
import Router from 'vue-router'
import Index from '@/components/Index'
import UserLogin from '@/components/user/Login'
import UserSetting from '@/components/user/Setting'
import UserRegister from '@/components/user/Register'
import UserResetPassword from '@/components/user/ResetPassword'
import ResetPasswordByUrl from '@/components/user/ResetPasswordByUrl'
import ItemIndex from '@/components/item/Index'
import ItemAdd from '@/components/item/Add'
import ItemPassword from '@/components/item/Password'
import ItemShow from '@/components/item/show/Index'
import ItemExport from '@/components/item/export/Index'
import ItemSetting from '@/components/item/setting/Index'
import PageIndex from '@/components/page/Index'
import PageEdit from '@/components/page/edit/Index'
import PageDiff from '@/components/page/Diff'
import Catalog from '@/components/catalog/Index'
import Notice from '@/components/notice/Index'
import Admin from '@/components/admin/Index'
import Team from '@/components/team/Index'
import TeamMember from '@/components/team/Member'
import TeamItem from '@/components/team/Item'

Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/',
      name: 'Index',
      component: Index
    },
    {
      path: '/user/login',
      name: 'UserLogin',
      component: UserLogin
    },
    {
      path: '/user/setting',
      name: 'UserSetting',
      component: UserSetting
    },
    {
      path: '/user/register',
      name: 'UserRegister',
      component: UserRegister
    },
    {
      path: '/user/resetPassword',
      name: 'UserResetPassword',
      component: UserResetPassword
    },
    {
      path: '/user/ResetPasswordByUrl',
      name: 'ResetPasswordByUrl',
      component: ResetPasswordByUrl
    },
    {
      path: '/item/index',
      name: 'ItemIndex',
      component: ItemIndex
    },
    {
      path: '/item/add',
      name: 'ItemAdd',
      component: ItemAdd
    },
    {
      path: '/item/password/:item_id',
      name: 'ItemPassword',
      component: ItemPassword
    },
    {
      path: '/:item_id',
      name: 'ItemShow',
      component: ItemShow
    },
    {
      path: '/item/export/:item_id',
      name: 'ItemExport',
      component: ItemExport
    },
    {
      path: '/item/setting/:item_id',
      name: 'ItemSetting',
      component: ItemSetting
    },
    {
      path: '/page/:page_id',
      name: 'PageIndex',
      component: PageIndex
    },
    {
      path: '/p/:unique_key',
      name: 'PageIndex',
      component: PageIndex
    },
    {
      path: '/page/edit/:item_id/:page_id',
      name: 'PageEdit',
      component: PageEdit
    },
    {
      path: '/page/diff/:page_id/:page_history_id',
      name: 'PageDiff',
      component: PageDiff
    },
    {
      path: '/catalog/:item_id',
      name: 'Catalog',
      component: Catalog
    },
    {
      path: '/notice/index',
      name: 'Notice',
      component: Notice
    },
    {
      path: '/admin/index',
      name: 'Admin',
      component: Admin
    },
    {
      path: '/team/index',
      name: 'Team',
      component: Team
    }, 
    {
      path: '/team/member/:team_id',
      name: 'TeamMember',
      component: TeamMember
    },
    {
      path: '/team/item/:team_id',
      name: 'TeamItem',
      component: TeamItem
    },
  ]
})
