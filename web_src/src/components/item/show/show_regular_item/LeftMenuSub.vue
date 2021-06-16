<template>
  <div>
    <template v-if="catalog.length">
      <el-submenu
        v-for="catalog2 in catalog"
        :index="catalog2.cat_id"
        :key="catalog2.cat_id"
      >
        <template slot="title">
          <img src="static/images/folder.png" />
          {{ catalog2.cat_name }}
        </template>
        <!-- 三级目录的页面 -->
        <template v-if="catalog2.pages">
          <el-menu-item
            v-for="page3 in catalog2.pages"
            :index="page3.page_id"
            :key="page3.page_id"
          >
            <i class="el-icon-document"></i>
            <a
              :href="randerUrl(page3.page_id)"
              @click.prevent="() => {}"
              :title="page3.page_title"
              :id="'left_page_' + page3.page_id"
              >{{ page3.page_title }}</a
            >
          </el-menu-item>
        </template>

        <!-- 子目录 -->
        <LeftMenuSub
          v-if="catalog2.catalogs.length"
          :catalog="catalog2.catalogs"
          :item_info="item_info"
        ></LeftMenuSub>
      </el-submenu>
    </template>
  </div>
</template>

<script>
export default {
  name: 'LeftMenuSub',
  props: {
    catalog: [],
    item_info: {}
  },
  data() {
    return {}
  },
  components: {},
  methods: {
    randerUrl(page_id) {
      if (!this.item_info) return
      var domain = this.item_info.item_domain
        ? this.item_info.item_domain
        : this.item_info.item_id
      return '#/' + domain + '/' + page_id
    }
  },
  mounted() {
    // console.log(this.catalog)
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.icon-folder {
  width: 18px;
  height: 15px;
  cursor: pointer;
}

.menu-icon-folder {
  margin-right: 5px;
  margin-top: -5px;
}

.el-menu-item,
.el-submenu__title {
  height: 46px;
  line-height: 46px;
}
.el-submenu .el-menu-item {
  height: 40px;
  line-height: 40px;
}
.el-menu-item {
  line-height: 40px;
  height: 40px;
  font-size: 12px;
}
.el-menu-item [class^='el-icon-'] {
  font-size: 17px;
  margin-bottom: 4px;
}
.el-submenu__title img {
  width: 14px;
  cursor: pointer;
  margin-left: 5px;
  margin-right: 10px;
  margin-bottom: 4px;
}
.el-menu-item:not(.is-active) a {
  color: #303133;
}
</style>
