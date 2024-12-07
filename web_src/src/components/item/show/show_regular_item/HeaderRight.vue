<!-- 附件 -->
<template>
  <div class="header-right float-right  mt-6 mr-5">
    <div>
      <el-tooltip
        v-if="item_info.item_edit && page_id"
        effect="dark"
        :content="$t('edit_page')"
      >
        <div
          class="icon-item"
          @click="
            edit_page_id = page_id
            showPageEdit = true
          "
        >
          <i class="fas fa-edit"></i>
        </div>
      </el-tooltip>
      <el-tooltip
        v-if="item_info.item_type == 3 && item_info.is_login"
        effect="dark"
        content="runapi项目请在runapi客户端编辑"
      >
        <div class="icon-item cursor-not-allowed">
          <i class="cursor-not-allowed far fa-edit"></i>
        </div>
      </el-tooltip>
      <el-tooltip effect="dark" :content="$t('share')" placement="top">
        <div class="icon-item" @click="showShare = true">
          <i class="far fa-share-nodes"></i>
        </div>
      </el-tooltip>
      <el-tooltip
        v-if="item_info.is_login"
        effect="dark"
        :content="$t('my_notice')"
        placement="top"
      >
        <div
          class="icon-item"
          @click="
            () => {
              $store.dispatch('changeNewMsg', 0)
              showMessage = true
            }
          "
        >
          <el-badge :value="$store.state.new_msg ? 'New' : ''">
            <i class="far fa-message"></i>
          </el-badge>
        </div>
      </el-tooltip>

      <el-tooltip
        v-if="item_info.is_login"
        effect="dark"
        :content="$t('user_center')"
        placement="top"
      >
        <div class="icon-item" @click="showUserSetting = true">
          <i class="far fa-user"></i>
        </div>
      </el-tooltip>

      <el-tooltip
        v-if="!item_info.is_login"
        effect="dark"
        :content="$t('login')"
        placement="top"
      >
        <div
          class="icon-item"
          @click="
            () => {
              $router.push({
                path: '/user/login'
              })
            }
          "
        >
          <i class="far fa-user"></i>
        </div>
      </el-tooltip>
      <el-tooltip
        v-if="!item_info.is_login"
        effect="dark"
        :content="$t('about_showdoc')"
        placement="top"
      >
        <div
          class="icon-item"
          @click="toOutLink('https://www.showdoc.com.cn/help')"
        >
          <i class="far fa-circle-info"></i>
        </div>
      </el-tooltip>

      <div class="inline" v-if="item_info.item_manage">
        <el-dropdown :show-timeout="0" trigger="hover">
          <div class="icon-item">
            <span class="el-dropdown-link">
              <i class="far fa-ellipsis"></i>
            </span>
          </div>

          <el-dropdown-menu slot="dropdown">
            <el-dropdown-item @click.native="showItemImport = true">
              <i
                style="transform: rotate(180deg)"
                class="mr-2 far fa-arrow-down-to-bracket"
              ></i>
              {{ $t('import') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showItemExport = true">
              <i class="mr-2 far fa-arrow-down-to-bracket"></i>
              {{ $t('export') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showMember = true">
              <i class="mr-2 far fa-users"></i>
              {{ $t('member_manage') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showRecycle = true">
              <i class="mr-2 far fa-trash"></i>
              {{ $t('recycle') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showItemUpdate = true">
              <i class="mr-2 far fa-edit"></i>
              {{ $t('update_item_base_info') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showChangeLog = true">
              <i class="mr-2 far fa-rectangle-vertical-history"></i>
              {{ $t('item_change_log') }}
            </el-dropdown-item>

            <el-dropdown-item @click.native="showAttorn = true">
              <i class="mr-2 fas fa-recycle"></i>
              {{ $t('attorn_item') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showArchive = true">
              <i class="mr-2 far fa-box-archive"></i>
              {{ $t('archive_item') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showDelete = true">
              <i class="mr-2 fae fa-trash-can"></i>
              {{ $t('delete_item') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showOpenApi = true">
              <i class="mr-2 fas fa-terminal"></i>
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
          $store.dispatch('reloadItem')
        }
      "
    ></PageEdit>


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
      :cancel="
        data => {
          this.showHistoryVersiong = false
        }
      "
    ></HistoryVersion>

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
      item_id: '',
      edit_page_id: 0, // 给PageEdit组件区分新建页面和编辑页面
      showTeam: false,
      copy_page_id: 0,
      showHistoryVersiong: false,
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
