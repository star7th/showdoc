<!-- 附件 -->
<template>
  <div class="header-right float-right  mt-5 mr-5">
    <div>
      <div
        v-if="item_info.item_edit"
        class="icon-item"
        @click="showPageEdit = true"
      >
        <el-tooltip effect="dark" :content="$t('edit_page')">
          <i class="el-icon-edit-outline"></i>
        </el-tooltip>
      </div>
      <div class="icon-item" @click="showShare = true">
        <el-tooltip effect="dark" :content="$t('share')" placement="top">
          <i class="el-icon-share"></i>
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
      <div class="inline" v-if="item_info.item_manage">
        <el-dropdown :show-timeout="0" trigger="hover">
          <div class="icon-item">
            <span class="el-dropdown-link">
              <i class="el-icon-more"></i>
            </span>
          </div>

          <el-dropdown-menu slot="dropdown">
            <el-dropdown-item divided @click.native="showItemUpdate = true">
              <i class="el-icon-edit-outline"></i>
              {{ $t('update_item_base_info') }}
            </el-dropdown-item>

            <el-dropdown-item @click.native="showAttorn = true">
              <i class="el-icon-refresh"></i>
              {{ $t('attorn_item') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showArchive = true">
              <i class="el-icon-dish"></i>
              {{ $t('archive_item') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showDelete = true">
              <i class="el-icon-delete"></i>
              {{ $t('delete_item') }}
            </el-dropdown-item>
          </el-dropdown-menu>
        </el-dropdown>
      </div>
    </div>

    <!-- 新建/编辑/复制页面 -->
    <PageEdit
      v-if="showPageEdit"
      :edit_page_id="page_id"
      :item_id="item_info.item_id"
      :callback="
        () => {
          showPageEdit = false
          reload()
        }
      "
    ></PageEdit>

    <!-- 分享项目地址 -->
    <Share
      v-if="showShare"
      :callback="
        () => {
          showShare = false
        }
      "
      :item_info="item_info"
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

    <!-- 更新项目信息的弹窗 -->
    <ItemUpdate
      v-if="showItemUpdate"
      :callback="
        () => {
          showItemUpdate = false
          reload()
        }
      "
      :item_id="item_info.item_id"
    >
    </ItemUpdate>

    <!-- 归档项目 -->
    <Archive
      v-if="showArchive"
      :callback="
        () => {
          showArchive = false
          goback()
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
  </div>
</template>

<script>
import Member from '@/components/item/setting/Member'
import Archive from '@/components/item/setting/Archive'
import Attorn from '@/components/item/setting/Attorn'
import Delete from '@/components/item/setting/Delete'
import ItemUpdate from '@/components/item/add/Basic'
import Share from '@/components/item/home/Share'
import PageEdit from '@/components/page/edit/Index'
export default {
  components: {
    Member,
    Archive,
    Attorn,
    Delete,
    ItemUpdate,
    Share,
    PageEdit
  },
  props: {
    item_info: {},
    page_id: 0
  },
  data() {
    return {
      lang: '',
      item_id: '',
      showTeam: false,
      showHistoryVersiong: false,
      showMember: false,
      showArchive: false,
      showAttorn: false,
      showItemUpdate: false,
      showShare: false,
      showDelete: false,
      showPageEdit: false
    }
  },

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
    // this.lang = 'en'
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
