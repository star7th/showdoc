import Vue from 'vue'
import Router from 'vue-router'
import Index from '@/components/Index'
import UserLogin from '@/components/user/Login'
import UserSetting from '@/components/user/setting/Index'
import UserRegister from '@/components/user/Register'
import loginByUserToken from '@/components/user/loginByUserToken'
import ItemIndex from '@/components/item/home/Index'
import ItemAdd from '@/components/item/add/Index'
import ItemPassword from '@/components/item/Password'
import ItemShow from '@/components/item/show/Index'
import ItemExport from '@/components/item/export/Index'
import ItemSetting from '@/components/item/setting/Index'
import PageIndex from '@/components/page/Index'
import PageEdit from '@/components/page/edit/Index'
import PageDiff from '@/components/page/Diff'
import Catalog from '@/components/catalog/Index'
import Admin from '@/components/admin/Index'
import Team from '@/components/team/Index'
import TeamMember from '@/components/team/Member'
import TeamItem from '@/components/team/Item'
import Attachment from '@/components/attachment/Index'
import ItemGroup from '@/components/item/group/Index'
import Message from '@/components/message/Index'

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
    {
      path: '/attachment/index',
      name: 'Attachment',
      component: Attachment
    },
    {
      path: '/item/group/index',
      name: 'ItemGroup',
      component: ItemGroup
    },
    {
      path: '/message/index',
      name: 'Message',
      component: Message
    },
    {
      path: '/user/loginByUserToken',
      name: 'loginByUserToken',
      component: loginByUserToken
    },

    // -------新路由加在分割线前面---------------
    {
      path: '/:item_id',
      name: 'ItemShow',
      component: ItemShow
    },
    {
      path: '/:item_id/:page_id(\\d+)',
      name: 'ItemShow',
      component: ItemShow
    }
  ]
})
