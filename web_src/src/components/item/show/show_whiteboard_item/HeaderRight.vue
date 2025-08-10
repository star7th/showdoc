<template>
  <div class="header-right float-right mt-6 mr-5">
    <div>
      <div class="icon-item" @click="showShare = true">
        <el-tooltip effect="dark" :content="$t('share')" placement="top">
          <i class="far fa-share-nodes"></i>
        </el-tooltip>
      </div>

      <div v-if="item_info.item_manage" class="icon-item" @click="save">
        <el-tooltip effect="dark" :content="$t('save')" placement="top">
          <i class="el-icon-s-shop"></i>
        </el-tooltip>
      </div>

      <div v-if="item_info.item_edit" class="icon-item" @click="exportImage">
        <el-tooltip effect="dark" :content="$t('export')" placement="top">
          <i class="far fa-arrow-down-to-bracket"></i>
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
          <i class="fal fa-users"></i>
        </el-tooltip>
      </div>

      <div v-if="!item_info.is_login" class="icon-item" @click="goLogin">
        <el-tooltip effect="dark" :content="$t('login')" placement="top">
          <i class="far fa-user"></i>
        </el-tooltip>
      </div>

      <div class="inline" v-if="item_info.item_manage">
        <el-dropdown :show-timeout="0" trigger="hover">
          <div class="icon-item">
            <span class="el-dropdown-link">
              <i class="far fa-ellipsis"></i>
            </span>
          </div>

          <el-dropdown-menu slot="dropdown">
            <el-dropdown-item divided @click.native="showItemUpdate = true">
              <i class="mr-2 far fa-edit"></i>
              {{ $t('update_item_base_info') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showArchive = true">
              <i class="mr-2 far fa-box-archive"></i>
              {{ $t('archive_item') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showAttorn = true">
              <i class="mr-2 far fa-recycle"></i>
              {{ $t('attorn_item') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showDelete = true">
              <i class="mr-2 far fa-trash-can"></i>
              {{ $t('delete_item') }}
            </el-dropdown-item>
          </el-dropdown-menu>
        </el-dropdown>
      </div>
    </div>

    <!-- 分享项目地址 -->
    <Share
      v-if="showShare"
      :callback="
        () => {
          showShare = false
        }
      "
      :item_info="item_info"
    />

    <!-- 成员&团队管理 -->
    <Member
      v-if="showMember"
      :callback="
        () => {
          showMember = false
        }
      "
      :item_id="item_info.item_id"
    />

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
    />

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
    />

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
    />

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
    />
  </div>
</template>

<script>
import ItemUpdate from '@/components/item/add/Basic'
import Archive from '@/components/item/setting/Archive'
import Attorn from '@/components/item/setting/Attorn'
import Delete from '@/components/item/setting/Delete'
import Member from '@/components/item/setting/Member'
import Share from '@/components/item/home/Share'

export default {
  components: { ItemUpdate, Archive, Attorn, Delete, Member, Share },
  props: {
    item_info: {},
    save: { type: Function, default: () => {} },
    exportImage: { type: Function, default: () => {} },
    clearCanvas: { type: Function, default: () => {} }
  },
  data() {
    return {
      showItemUpdate: false,
      showArchive: false,
      showAttorn: false,
      showDelete: false,
      showShare: false,
      showMember: false
    }
  },
  methods: {
    goback() {
      this.$router.push({ path: '/item/index' })
      setTimeout(() => {
        window.location.reload()
      }, 200)
    },
    goLogin() {
      this.$router.push({ path: '/user/login' })
    }
  }
}
</script>

<style scoped>
.icon-item {
  background-color: white;
  width: 40px;
  height: 40px;
  font-size: 13px;
  justify-content: center;
  align-items: center;
  display: inline-flex;
  margin-right: 10px;
  border-radius: 10px;
  box-shadow: 0 0 4px #0000001a;
  cursor: pointer;
}
</style>
