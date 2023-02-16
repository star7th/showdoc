<!-- 附件 -->
<template>
  <div class="header-right float-right  mt-5 mr-5">
    <div>
      <div
        v-if="item_info.item_edit"
        class="icon-item"
        @click="
          edit_page_id = 0
          showPageEdit = true
        "
      >
        <el-tooltip effect="dark" :content="$t('new_page')" placement="top">
          <i class="el-icon-plus"></i>
        </el-tooltip>
      </div>
      <div
        v-if="item_info.item_edit"
        class="icon-item"
        @click="
          edit_page_id = page_id
          showPageEdit = true
        "
      >
        <el-tooltip effect="dark" :content="$t('edit_page')">
          <i class="el-icon-edit-outline"></i>
        </el-tooltip>
      </div>
      <div
        v-if="item_info.item_type == 3 && item_info.is_login"
        class="icon-item"
      >
        <el-tooltip effect="dark" content="runapi项目请在runapi客户端编辑">
          <i class=" cursor-not-allowed el-icon-edit-outline"></i>
        </el-tooltip>
      </div>
      <div
        v-if="item_info.item_edit"
        class="icon-item"
        @click="showCatalog = true"
      >
        <el-tooltip effect="dark" :content="$t('new_catalog')" placement="top">
          <i class="el-icon-folder-add"></i>
        </el-tooltip>
      </div>
      <div class="icon-item" @click="showShare = true">
        <el-tooltip effect="dark" :content="$t('share')" placement="top">
          <i class="el-icon-share"></i>
        </el-tooltip>
      </div>
      <div
        v-if="
          item_info.item_manage ||
            (item_info.item_type == 3 && item_info.is_login)
        "
        class="icon-item"
        @click="showItemExport = true"
      >
        <el-tooltip effect="dark" :content="$t('export')" placement="top">
          <i class="el-icon-download"></i>
        </el-tooltip>
      </div>
      <div
        v-if="item_info.item_manage"
        class="icon-item"
        @click="showItemImport = true"
      >
        <el-tooltip effect="dark" :content="$t('import')" placement="top">
          <i class="el-icon-upload2"></i>
        </el-tooltip>
      </div>
      <div
        v-if="item_info.item_manage"
        class="icon-item"
        @click="showMember = true"
      >
        <el-tooltip
          effect="dark"
          :content="$t('member_manage')"
          placement="top"
        >
          <i class="el-icon-wind-power"></i>
        </el-tooltip>
      </div>
      <div
        v-if="item_info.is_login"
        class="icon-item"
        @click="
          () => {
            $store.dispatch('changeNewMsg', 0)
            showMessage = true
          }
        "
      >
        <el-tooltip effect="dark" :content="$t('my_notice')" placement="top">
          <el-badge :value="$store.state.new_msg ? 'New' : ''">
            <i class="el-icon-bell"></i
          ></el-badge>
        </el-tooltip>
      </div>
      <div
        v-if="item_info.is_login"
        class="icon-item"
        @click="showUserSetting = true"
      >
        <el-tooltip effect="dark" :content="$t('user_center')" placement="top">
          <i class="el-icon-user"></i>
        </el-tooltip>
      </div>
      <div
        v-if="!item_info.is_login"
        class="icon-item"
        @click="
          () => {
            $router.push({
              path: '/user/login'
            })
          }
        "
      >
        <el-tooltip effect="dark" :content="$t('login')" placement="top">
          <i class="el-icon-user"></i>
        </el-tooltip>
      </div>
      <div
        v-if="!item_info.is_login"
        class="icon-item"
        @click="toOutLink('https://www.showdoc.com.cn/help')"
      >
        <el-tooltip
          effect="dark"
          :content="$t('about_showdoc')"
          placement="top"
        >
          <i class="el-icon-help"></i>
        </el-tooltip>
      </div>

      <div class="inline" v-if="item_info.item_edit">
        <el-dropdown :show-timeout="0" trigger="hover">
          <div class="icon-item">
            <span class="el-dropdown-link">
              <i class="el-icon-more"></i>
            </span>
          </div>

          <el-dropdown-menu slot="dropdown">
            <el-dropdown-item
              @click.native="
                edit_page_id = 0
                copy_page_id = page_id
                showPageEdit = true
              "
            >
              <i class="el-icon-document"></i>
              {{ $t('copy_page') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showHistoryVersiong = true">
              <i class="el-icon-goods"></i>
              {{ $t('page_history_version') }}
            </el-dropdown-item>

            <el-dropdown-item @click.native="showSortPage = true">
              <i class="el-icon-sort"></i>
              {{ $t('sort_page') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showPageInfo">
              <i class="el-icon-info"></i>
              {{ $t('page_info') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="deletePage">
              <i class="el-icon-delete"></i>
              {{ $t('delete_page') }}
            </el-dropdown-item>
            <el-dropdown-item
              v-if="item_info.item_manage"
              @click.native="showRecycle = true"
            >
              <i class="el-icon-coffee"></i>
              {{ $t('recycle') }}
            </el-dropdown-item>
            <el-dropdown-item
              v-if="item_info.item_manage"
              divided
              @click.native="showItemUpdate = true"
            >
              <i class="el-icon-edit-outline"></i>
              {{ $t('update_item_base_info') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showChangeLog = true">
              <i class="el-icon-toilet-paper"></i>
              {{ $t('item_change_log') }}
            </el-dropdown-item>

            <el-dropdown-item
              v-if="item_info.item_manage"
              @click.native="showAttorn = true"
            >
              <i class="el-icon-refresh"></i>
              {{ $t('attorn_item') }}
            </el-dropdown-item>
            <el-dropdown-item
              v-if="item_info.item_manage"
              @click.native="showArchive = true"
            >
              <i class="el-icon-dish"></i>
              {{ $t('archive_item') }}
            </el-dropdown-item>
            <el-dropdown-item
              v-if="item_info.item_manage"
              @click.native="showDelete = true"
            >
              <i class="el-icon-delete"></i>
              {{ $t('delete_item') }}
            </el-dropdown-item>
            <el-dropdown-item
              v-if="item_info.item_manage"
              @click.native="showOpenApi = true"
            >
              <i class="el-icon-magic-stick"></i>
              {{ $t('open_api') }}
            </el-dropdown-item>
          </el-dropdown-menu>
        </el-dropdown>
      </div>
    </div>

    <!-- 新建/编辑/复制页面 -->
    <PageEdit
      v-if="showPageEdit"
      :edit_page_id="edit_page_id"
      :item_id="item_info.item_id"
      :copy_page_id="copy_page_id"
      :callback="
        () => {
          showPageEdit = false
          reload()
        }
      "
    ></PageEdit>

    <!-- 目录管理 -->
    <Catalog
      v-if="showCatalog"
      :item_id="item_info.item_id"
      :callback="
        () => {
          showCatalog = false
          reload()
        }
      "
    ></Catalog>

    <!-- 分享页面 -->
    <Share
      v-if="showShare"
      :callback="
        () => {
          showShare = false
        }
      "
      :item_info="item_info"
      :page_info="page_info"
    >
    </Share>

    <!-- 成员&团队管理 -->
    <Member
      v-if="showMember"
      :callback="
        () => {
          showMember = false
        }
      "
      :item_id="item_info.item_id"
    ></Member>

    <!-- 历史版本 -->
    <HistoryVersion
      :page_id="page_id"
      :is_show_recover_btn="false"
      :is_modal="false"
      v-if="showHistoryVersiong"
      :callback="
        data => {
          this.showHistoryVersiong = false
        }
      "
    ></HistoryVersion>

    <!-- 页面排序 -->
    <SortPage
      v-if="showSortPage"
      :callback="
        () => {
          showSortPage = false
          reload()
        }
      "
      :item_id="item_id"
      :page_id="page_id"
      :cat_id="page_info.cat_id"
    ></SortPage>

    <!-- 回收站的弹窗 -->
    <Recycle
      v-if="showRecycle"
      :callback="
        () => {
          showRecycle = false
        }
      "
      :item_id="item_info.item_id"
    >
    </Recycle>

    <!-- 更新项目信息的弹窗 -->
    <ItemUpdate
      v-if="showItemUpdate"
      :callback="
        () => {
          showItemUpdate = false
        }
      "
      :item_id="item_info.item_id"
    >
    </ItemUpdate>

    <!-- 项目变更日志对话框 -->
    <ChangeLog
      v-if="showChangeLog"
      :callback="
        () => {
          showChangeLog = false
        }
      "
      :item_id="item_id"
      :page_id="page_id"
    ></ChangeLog>

    <!-- 归档项目 -->
    <Archive
      v-if="showArchive"
      :callback="
        () => {
          showArchive = false
        }
      "
      :item_id="item_info.item_id"
    >
    </Archive>

    <!-- 转让项目 -->
    <Attorn
      v-if="showAttorn"
      :callback="
        () => {
          showAttorn = false
          goback()
        }
      "
      :item_id="item_info.item_id"
    >
    </Attorn>

    <!-- 删除项目 -->
    <Delete
      v-if="showDelete"
      :callback="
        () => {
          showDelete = false
          goback()
        }
      "
      :item_id="item_info.item_id"
    >
    </Delete>

    <!-- 开放api弹窗 -->
    <OpenApi
      v-if="showOpenApi"
      :callback="
        () => {
          showOpenApi = false
        }
      "
      :item_id="item_info.item_id"
    >
    </OpenApi>

    <!-- 项目导出弹窗 -->
    <ItemExport
      v-if="showItemExport"
      :callback="
        () => {
          showItemExport = false
        }
      "
      :item_id="item_info.item_id"
    >
    </ItemExport>

    <!-- 导入弹窗 -->
    <ItemImport
      v-if="showItemImport"
      :callback="
        () => {
          showItemImport = false
        }
      "
      :item_id="item_info.item_id"
    >
    </ItemImport>

    <!-- 用户设置（用户中心） -->
    <UserSetting
      v-if="showUserSetting"
      :callback="
        () => {
          showUserSetting = false
        }
      "
    ></UserSetting>

    <!-- 我的消息 -->
    <Message
      v-if="showMessage"
      :callback="
        () => {
          showMessage = false
        }
      "
    ></Message>
  </div>
</template>

<script>
import PageEdit from '@/components/page/edit/Index'
import Catalog from '@/components/catalog/Index'
import Member from '@/components/item/setting/Member'
import OpenApi from '@/components/item/setting/OpenApi'
import HistoryVersion from '@/components/page/edit/HistoryVersion'
import SortPage from '@/components/page/edit/SortPage'
import ChangeLog from './ChangeLog'
import Share from './Share'
import Recycle from '@/components/item/setting/Recycle'
import Archive from '@/components/item/setting/Archive'
import Attorn from '@/components/item/setting/Attorn'
import Delete from '@/components/item/setting/Delete'
import ItemUpdate from '@/components/item/add/Basic'
import ItemExport from '@/components/item/export/Index'
import ItemImport from '@/components/item/import/Index'
import UserSetting from '@/components/user/setting/Index'
import Message from '@/components/message/Index'

export default {
  components: {
    PageEdit,
    Catalog,
    Member,
    HistoryVersion,
    SortPage,
    ChangeLog,
    OpenApi,
    Recycle,
    Archive,
    Attorn,
    Delete,
    ItemUpdate,
    ItemExport,
    ItemImport,
    Share,
    UserSetting,
    Message
  },
  props: {
    searchItem: () => {},
    page_id: '',
    item_info: {},
    page_info: {}
  },
  data() {
    return {
      showPageEdit: false,
      showCatalog: false,
      item_id: '',
      edit_page_id: 0, // 给PageEdit组件区分新建页面和编辑页面
      showTeam: false,
      copy_page_id: 0,
      showHistoryVersiong: false,
      showSortPage: false,
      showMember: false,
      showOpenApi: false,
      showRecycle: false,
      showArchive: false,
      showAttorn: false,
      showDelete: false,
      showItemUpdate: false,
      showChangeLog: false,
      showItemExport: false,
      showItemImport: false,
      showShare: false,
      showUserSetting: false,
      showMessage: false
    }
  },
  computed: {},
  methods: {
    showPageInfo() {
      var html =
        '本页面由 ' +
        this.page_info.author_username +
        ' 于 ' +
        this.page_info.addtime +
        ' 更新'
      this.$alert(html)
    },
    deletePage() {
      var page_id = this.page_id > 0 ? this.page_id : 0
      this.$confirm(this.$t('comfirm_delete'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/page/delete', {
          page_id: page_id
        }).then(data => {
          window.location.reload()
        })
      })
    },
    goback() {
      // 为了防止对话框遮罩来不及关闭，延迟0.5秒
      setTimeout(() => {
        this.$router.push({
          path: '/item/index'
        })
      }, 500)
    }
  },
  mounted() {
    this.item_id = this.item_info.item_id
  }
}
</script>
<style scoped>
.header-right .el-dropdown {
  font-size: 16px;
}
.icon-item {
  background-color: white;
  width: 40px;
  height: 40px;
  font-size: 16px;
  justify-content: center; /*水平居中*/
  align-items: center; /*垂直居中*/
  display: inline-flex;
  margin-right: 10px;
  border-radius: 10px;
  box-shadow: 0 0 4px #0000001a;
  cursor: pointer;
}
.icon-item a {
  color: black;
}
.el-dropdown-link,
a {
  color: #343a40;
}
</style>
