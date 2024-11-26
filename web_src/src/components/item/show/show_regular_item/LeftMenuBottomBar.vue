<!-- 附件 -->
<template>
  <div>
    <div
      v-if="item_info.item_edit"
      class="left-menu-bottom-bar"
      id="left-menu-bottom-bar"
    >
      <el-tooltip effect="dark" :content="$t('new_page')" placement="top">
        <div class="bottom-bar-item-icon" @click="showPageEdit = true">
          <i class="far fa-plus"></i>
        </div>
      </el-tooltip>
      <el-tooltip effect="dark" :content="$t('copy_page')" placement="top">
        <div
          class="bottom-bar-item-icon"
          @click="
            copyPageId = page_id
            showPageEdit = true
          "
        >
          <i class=" far fa-clone"></i>
        </div>
      </el-tooltip>
      <el-tooltip effect="dark" :content="$t('new_catalog')" placement="top">
        <div class="bottom-bar-item-icon" @click="showCatalog = true">
          <i class="far fa-folder-plus"></i>
        </div>
      </el-tooltip>

      <div class="bottom-bar-item-icon">
        <el-dropdown :show-timeout="0" trigger="hover">
          <div class="bottom-bar-item-icon">
            <span class="el-dropdown-link">
              <i class=" fas fa-ellipsis"></i>
            </span>
          </div>

          <el-dropdown-menu slot="dropdown">
            <el-dropdown-item @click.native="showRecycle = true">
              <i class=" far fa-trash"></i>
              {{ $t('recycle') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showHistoryVersiong = true">
              <i class=" far fa-rectangle-history"></i>
              {{ $t('page_history_version') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showSortPage = true">
              <i class=" far fa-sort"></i>
              {{ $t('sort_page') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="showPageInfo">
              <i class=" far fa-circle-info"></i>
              {{ $t('page_info') }}
            </el-dropdown-item>
            <el-dropdown-item @click.native="deletePage">
              <i class=" far fa-trash-can"></i>
              {{ $t('delete_page') }}
            </el-dropdown-item>
          </el-dropdown-menu>
        </el-dropdown>
      </div>

      <!-- 新建/编辑/复制页面 -->
      <PageEdit
        v-if="showPageEdit"
        :edit_page_id="0"
        :item_id="item_id"
        :copy_page_id="copyPageId"
        :callback="
          () => {
            showPageEdit = false
            $store.dispatch('reloadItem')
          }
        "
      ></PageEdit>

      <!-- 目录管理 -->
      <Catalog
        v-if="showCatalog"
        :item_id="item_id"
        :callback="
          () => {
            showCatalog = false
            $store.dispatch('reloadItem')
          }
        "
      ></Catalog>

      <!-- 回收站的弹窗 -->
      <Recycle
        v-if="showRecycle"
        :callback="
          () => {
            showRecycle = false
          }
        "
        :item_id="item_id"
      >
      </Recycle>

      <!-- 历史版本 -->
      <HistoryVersion
        :page_id="page_id"
        :is_show_recover_btn="false"
        :is_modal="false"
        v-if="showHistoryVersiong"
        :callback="
          data => {
            showHistoryVersiong = false
          }
        "
        :cancel="
          () => {
            showHistoryVersiong = false
          }
        "
      ></HistoryVersion>

      <!-- 页面排序 -->
      <SortPage
        v-if="showSortPage"
        :callback="
          () => {
            showSortPage = false
            $store.dispatch('reloadItem')
          }
        "
        :item_id="item_id"
        :page_id="page_id"
        :cat_id="page_info.cat_id"
      ></SortPage>
    </div>
  </div>
</template>

<style></style>

<script>
import PageEdit from '@/components/page/edit/Index'
import Catalog from '@/components/catalog/Index'
import SortPage from '@/components/page/edit/SortPage'
import Recycle from '@/components/item/setting/Recycle'
import HistoryVersion from '@/components/page/edit/HistoryVersion'

export default {
  props: {
    searchItem: () => {},
    page_id: '',
    item_id: '',
    page_info: {},
    item_info: {}
  },
  data() {
    return {
      showPageEdit: false,
      showCatalog: false,
      showSortPage: false,
      showRecycle: false,
      copyPageId: 0,
      showHistoryVersiong: false
    }
  },
  components: { PageEdit, Catalog, SortPage, Recycle, HistoryVersion },
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
          this.$store.dispatch('reloadItem')
        })
      })
    }
  },
  mounted() {
    // const page_op_tops = localStorage.getItem('page_op_tops')
    // if (!page_op_tops) {
    //   this.$alert(
    //     'showdoc功能位置发生了变更，新增页面/目录以及删除页面/目录等功能入口已经移动到左侧目录树的底部操作栏',
    //     {
    //       confirmButtonText: '我已知晓'
    //     }
    //   )
    //   localStorage.setItem('page_op_tops', 1)
    // }
  }
}
</script>
<style scoped>
.left-menu-bottom-bar {
  height: 50px;
  position: fixed;
  bottom: 0;
  width: 310px;
  border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.left-menu-bottom-bar .bottom-bar-item-icon {
  width: calc(25% - 4px);
  height: 50px;
  justify-content: center; /*水平居中*/
  align-items: center; /*垂直居中*/
  display: inline-flex;
  cursor: pointer;
}

.left-menu-bottom-bar > .bottom-bar-item-icon {
  border-right: 1px solid rgba(0, 0, 0, 0.05);
}

.el-dropdown-link,
a {
  color: #343a40;
}
/*小屏设备（但不是移动端设备） */
@media (max-width: 1300px) {
  .left-menu-bottom-bar {
    width: 300px;
  }
}
</style>
