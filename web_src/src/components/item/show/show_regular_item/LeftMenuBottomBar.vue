<!-- 附件 -->
<template>
  <div class="left-menu-bottom-bar" id="left-menu-bottom-bar">
    <div class="bottom-bar-item-icon" @click="showPageEdit = true">
      <el-tooltip effect="dark" :content="$t('new_page')" placement="top">
        <i class="el-icon-circle-plus-outline"></i>
      </el-tooltip>
    </div>
    <div class="bottom-bar-item-icon" @click="showCatalog = true">
      <el-tooltip effect="dark" :content="$t('new_catalog')" placement="top">
        <i class="el-icon-folder-add"></i>
      </el-tooltip>
    </div>
    <div class="bottom-bar-item-icon" @click="showSortPage = true">
      <el-tooltip effect="dark" :content="$t('sort_page')" placement="top">
        <i class="el-icon-sort"></i>
      </el-tooltip>
    </div>
    <div class="bottom-bar-item-icon">
      <el-dropdown :show-timeout="0" trigger="hover">
        <div class="bottom-bar-item-icon">
          <span class="el-dropdown-link">
            <i class="item-icon-more el-icon-more"></i>
          </span>
        </div>

        <el-dropdown-menu slot="dropdown">
          <el-dropdown-item @click.native="showRecycle = true">{{
            $t('recycle')
          }}</el-dropdown-item>
        </el-dropdown-menu>
      </el-dropdown>
    </div>

    <!-- 新建/编辑/复制页面 -->
    <PageEdit
      v-if="showPageEdit"
      :edit_page_id="0"
      :item_id="item_id"
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
      :item_id="item_id"
      :callback="
        () => {
          showCatalog = false
          reload()
        }
      "
    ></Catalog>

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
      :item_id="item_id"
    >
    </Recycle>
  </div>
</template>

<style></style>

<script>
import PageEdit from '@/components/page/edit/Index'
import Catalog from '@/components/catalog/Index'
import SortPage from '@/components/page/edit/SortPage'
import Recycle from '@/components/item/setting/Recycle'

export default {
  props: {
    searchItem: () => {},
    page_id: '',
    item_id: '',
    page_info: {}
  },
  data() {
    return {
      showPageEdit: false,
      showCatalog: false,
      showSortPage: false,
      showRecycle: false
    }
  },
  components: { PageEdit, Catalog, SortPage, Recycle },
  computed: {},
  methods: {},
  mounted() {}
}
</script>
<style scoped>
.left-menu-bottom-bar {
  height: 50px;
  position: fixed;
  bottom: 0;
  width: 300px;
}

.left-menu-bottom-bar .bottom-bar-item-icon {
  width: calc(25% - 4px);
  height: 50px;
  font-size: 20px;
  justify-content: center; /*水平居中*/
  align-items: center; /*垂直居中*/
  display: inline-flex;
  cursor: pointer;
}

.el-dropdown-link,
a {
  color: #343a40;
}
</style>
