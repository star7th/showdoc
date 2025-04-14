<template>
  <div class="hello">
    <div class="header">
      <div @click="reload" class="float-left ml-10">
        <div class="logo">
          <div class="logo-img-div">
            <img class="logo-img" src="@/assets/Logo.svg" />
          </div>
          <div class="">
            <div class=" font-bold logo-title">ShowDoc</div>
            <div class="v3-color-aux logo-desc">
              {{ $t('home_logo_title') }}
            </div>
          </div>
        </div>
      </div>
      <HeaderRight :isAdmin="isAdmin"></HeaderRight>
    </div>
    <div class="container" :class="{ 'card-view-mode': viewMode === 'card' }">
      <div class="left-side inline-block">
        <div class="all-star-item-group">
          <div
            :class="
              itemGroupId === 0
                ? 'item-one-block item-one-block-active'
                : 'item-one-block'
            "
            @click="changeGroup(0)"
          >
            <div class="item-one-block-content">
              <i class="mr-1 fas fa-notes"></i>
              {{ $t('all_items') }}
            </div>
          </div>
          <div
            :class="
              itemGroupId === -1
                ? 'item-one-block item-one-block-active'
                : 'item-one-block'
            "
            @click="changeGroup(-1)"
          >
            <div class="item-one-block-content">
              <i class="mr-1 v3-color-yellow fas fa-star"></i>
              {{ $t('star_items') }}
            </div>
          </div>
        </div>
        <el-divider class="item-group-divider mt-2 mb-2"></el-divider>
        <div class="divider-item-block mb-3">
          <div class="divider-text">{{ $t('group') }}</div>
          <div class="divider-icon" @click="showItemGroupCom = true">
            <el-tooltip
              effect="dark"
              :content="$t('item_group_desc')"
              placement="top"
              ><i class="el-icon-plus mr-1"></i>
            </el-tooltip>
          </div>
        </div>
        <div>
          <div
            v-for="one in itemGroupList"
            :key="one.item_id"
            :class="
              itemGroupId == one.id
                ? 'item-one-block item-one-block-active'
                : 'item-one-block'
            "
            @click="changeGroup(one.id)"
          >
            <div class="item-one-block-content">
              <i class="mr-1 far v3-font-size-sm fa-hashtag"></i>
              {{ one.group_name }}
            </div>
          </div>
        </div>
        <div v-if="$lang == 'zh-cn'" class="left-bottom-bar">
          <div class="content">
            <i class="far fa-fire "></i>
            调试API并自动生成文档
            <a
              class="text-link ml-2"
              @click="toOutLink('https://www.showdoc.com.cn/runapi')"
              >试试</a
            >
          </div>
        </div>
      </div>
      <div class="right-side align-top  inline-block">
        <!-- 搜索区域 -->
        <div class="search-and-view-control">
          <div class="search-box-div">
            <div class="search-box el-input el-input--prefix">
              <el-input
                autocomplete="off"
                type="text"
                class="search-input"
                validateevent="true"
                :clearable="true"
                v-model="keyword"
                :placeholder="$t('search_keyword')"
              />
              <span class="el-input__prefix">
                <i
                  style="font-size: 16px;line-height: 60px;margin-left: 5px;"
                  class="el-input__icon el-icon-search"
                ></i>
              </span>
            </div>
          </div>
        </div>

        <div class="divider-item-block mt-3 mb-3">
          <div class="divider-text">{{ selectedGroupName }}</div>
          <!-- 视图切换按钮 -->
          <div class="view-switcher">
            <el-tooltip :content="$t('list_view')" placement="top">
              <div
                class="view-switch-btn"
                :class="{ active: viewMode === 'list' }"
                @click="viewMode = 'list'"
              >
                <i class="fas fa-list-ul"></i>
              </div>
            </el-tooltip>
            <el-tooltip :content="$t('card_view')" placement="top">
              <div
                class="view-switch-btn"
                :class="{ active: viewMode === 'card' }"
                @click="viewMode = 'card'"
              >
                <i class="fas fa-th-large"></i>
              </div>
            </el-tooltip>
          </div>
        </div>

        <!-- 项目列表组件  -->
        <ItemListCom
          v-if="!showSearch && viewMode === 'list'"
          :itemList="itemList"
          :getItemList="getItemList"
          :itemGroupId="itemGroupId"
        >
        </ItemListCom>

        <!-- 项目卡片视图组件 -->
        <ItemCardList
          v-if="!showSearch && viewMode === 'card'"
          :itemList="itemList"
          :getItemList="getItemList"
          :itemGroupId="itemGroupId"
        >
        </ItemCardList>

        <div v-if="itemList.length === 0" class="empty">
          <div class="icon">
            <i class="el-icon-warning"></i>
          </div>
          <div class="text">{{ $t('no_items') }}</div>
        </div>

        <!-- 搜索结果列表组件 -->
        <Search
          v-if="showSearch"
          :keyword="keyword"
          :itemList="itemList"
        ></Search>

        <!-- 新建项目按钮 -->
        <div
          class="content-wrapper"
          :class="{ 'card-view-mode': viewMode === 'card' }"
        >
          <itemAdd
            :itemGroupId="itemGroupId"
            :callback="
              () => {
                getItemList()
              }
            "
          ></itemAdd>
        </div>
      </div>
    </div>
    <itemGroupCom
      v-if="showItemGroupCom"
      :callback="
        () => {
          showItemGroupCom = false
          getItemGroupList()
        }
      "
    ></itemGroupCom>

    <!-- 则引入通知组件  -->
    <Notify :popup="false"></Notify>

    <!-- 返回顶部 -->
    <el-backtop></el-backtop>
  </div>
</template>

<script>
import draggable from 'vuedraggable'
import itemGroupCom from '@/components/item/group/Index'
import itemAdd from '@/components/item/add/Index'
import HeaderRight from './HeaderRight.vue'
import Search from './Search.vue'
import ItemListCom from './ItemList.vue'
import Notify from '@/components/common/Notify'
import ItemCardList from './ItemCardList.vue'
import { getUserInfo } from '@/models/user.js'
export default {
  components: {
    draggable,
    itemGroupCom,
    HeaderRight,
    itemAdd,
    Search,
    ItemListCom,
    ItemCardList,
    Notify
  },
  data() {
    return {
      currentDate: new Date(),
      itemList: [],
      isAdmin: false,
      keyword: '',
      username: '',
      showSearch: false,
      itemGroupId: '',
      itemGroupList: [],
      loading: false,
      showItemGroupCom: false,
      viewMode: 'list' // 默认为列表视图
    }
  },
  computed: {
    // 已选中的分组名字
    selectedGroupName: function() {
      if (this.keyword) {
        if (this.$lang == 'en') {
          return `Search results with "${this.keyword}"`
        } else {
          return `含有"${this.keyword}"的搜索结果`
        }
      }
      if (this.itemGroupId === 0) {
        return this.$t('all_items')
      }
      if (this.itemGroupId === -1) {
        return this.$t('star_items')
      }
      for (let index = 0; index < this.itemGroupList.length; index++) {
        const element = this.itemGroupList[index]
        if (parseInt(element.id) == this.itemGroupId) {
          return element.group_name
        }
      }
    }
  },
  watch: {
    // 监听搜索词的变化
    keyword: function(val) {
      if (val) {
        // 当输入的字符只有一个长度的时候，是中文才会搜索。英文或者数字不会搜索
        if (val && val.length == 1) {
          // 验证是否是中文
          var pattern = new RegExp('[\u4E00-\u9FA5]+')
          if (pattern.test(val)) {
            // alert('该字符串是中文')
            this.showSearch = true
          }
        } else {
          this.showSearch = true
        }
      } else {
        this.showSearch = false
      }
    },
    // 保存视图模式到本地存储
    viewMode: function(val) {
      localStorage.setItem('defaultViewMode', val)
    }
  },
  methods: {
    getItemList() {
      this.loading = true
      const itemGroupId = this.itemGroupId
      this.request('/api/item/myList', {
        item_group_id: itemGroupId
      }).then(data => {
        this.loading = false
        this.itemList = data.data
      })
    },
    getItemGroupList() {
      this.request('/api/itemGroup/getList', {}).then(data => {
        this.itemGroupList = data.data
        const deaultItemGroupId = localStorage.getItem('deaultItemGroupId')
        // 循环欧判断记住的id是否还存在列表中
        this.itemGroupList.map(element => {
          if (element.id == deaultItemGroupId) {
            this.itemGroupId = parseInt(deaultItemGroupId)
          }
        })
        this.getItemList()
      })
    },
    changeGroup(id) {
      this.itemGroupId = parseInt(id)
      localStorage.setItem('deaultItemGroupId', id)
      this.showSearch = false // 如果正在展示搜索结果，则切换分组时候，还原
      this.keyword = ''
      this.getItemList() // 重新获取列表
    },
    reload() {
      window.location.reload()
    },
    checkAdmin() {
      var that = this
      getUserInfo(function(data) {
        if (data.error_code === 0) {
          that.username = data.data.username
          if (data.data.groupid == 1) {
            that.isAdmin = true
          }
        }
      })
    }
  },
  // 跳转到外部链接
  toOutLink(url) {
    window.open(url)
  },
  mounted() {
    const deaultItemGroupId = localStorage.getItem('deaultItemGroupId')
    if (deaultItemGroupId === null) {
      this.getItemList()
      this.itemGroupId = 0
    } else {
      this.itemGroupId = parseInt(deaultItemGroupId)
    }

    // 从本地存储中获取视图模式
    const defaultViewMode = localStorage.getItem('defaultViewMode')
    if (defaultViewMode) {
      this.viewMode = defaultViewMode
    }

    this.getItemGroupList()
    this.checkAdmin()
  },
  beforeDestroy() {
    this.$message.closeAll()
  }
}
</script>

<style scoped>
.header {
  height: 90px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.logo {
  display: flex;
  align-items: center;
  cursor: pointer;
  height: 90px;
}

.logo-img-div {
  display: flex;
  align-items: center;
  margin-right: 10px;
}

.logo-img {
  width: 50px;
  height: 50px;
}
.logo-title {
  font-size: 20px;
}
.logo-desc {
  font-size: 10px;
}

.el-dropdown-link,
a {
  color: #343a40;
}

.container {
  max-width: 870px;
  margin: 0 auto;
  transition: max-width 0.2s ease;
}

/* 卡片视图模式下容器变宽 */
.container.card-view-mode {
  max-width: 1050px;
}

.left-side {
  width: 230px;
  padding-top: 40px;
  border-right: 1px solid rgba(0, 0, 0, 0.05);
  min-height: calc(100vh - 150px);
  transition: width 0.3s ease;
}

/* 卡片视图下左侧侧边栏可以更窄一些 */
.card-view-mode .left-side {
  width: 200px;
}

.item-one-block {
  height: 40px;
  position: relative;
  border-radius: 8px;
  cursor: pointer;
  padding-left: 10px;
  margin-bottom: 2px;
}

.item-one-block:hover {
  background-color: white;
  margin-right: 10px;
}
.item-one-block-active {
  background-color: white;
  margin-right: 10px;
}

.item-one-block-content {
  padding-left: 5px;
  position: absolute;
  top: 50%;
  transform: translate(0, -50%);
}

.divider-text {
  font-size: 11px;
  color: #9b9b9b;
  margin-left: 1px;
}
.divider-icon {
  font-size: 11px;
  color: #9b9b9b;
  float: right;
  display: inline;
  cursor: pointer;
  margin-right: 5px;
}
.item-group-divider {
  background-color: #000;
  opacity: 0.05;
}

.right-side {
  padding-top: 40px;
  padding-left: 20px;
  width: calc(100% - 250px);
  box-sizing: border-box;
  transition: width 0.3s ease, padding-left 0.3s ease;
}

/* 卡片视图下右侧内容区更宽 */
.card-view-mode .right-side {
  width: calc(100% - 220px);
  padding-left: 30px;
}

.search-input {
  width: 100%;
  max-width: 600px;
  transition: max-width 0.2s ease;
}

.card-view-mode .search-input {
  max-width: 100%;
}

.search-input >>> input {
  border-radius: 8px;
  height: 50px;
  padding-left: 40px;
  font-size: 14px;
}

.search-and-view-control {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.search-box-div {
  flex: 1;
}

.view-switcher {
  display: flex;
  align-items: center;
}

.view-switch-btn {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 3px;
  cursor: pointer;
  margin-left: 5px;
  background: #f9f9f9;
  color: #9b9b9b;
  transition: all 0.2s;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.view-switch-btn:hover {
  background: #f0f0f0;
  color: #606266;
}

.view-switch-btn.active {
  background: #f0f0f0;
  color: #333;
  border-color: rgba(0, 0, 0, 0.1);
}

.empty {
  margin: 10% auto;
  width: 400px;
  text-align: center;
  color: #909399;
  opacity: 0.7;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.empty .icon {
  font-size: 60px;
  margin-bottom: 20px;
  color: #dcdfe6;
}

.empty .text {
  font-size: 14px;
  margin-bottom: 20px;
}
.left-bottom-bar {
  position: fixed;
  bottom: 35px;
  text-align: center;
  width: 230px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.left-bottom-bar .content {
  width: 200px;
  height: 30px;
  line-height: 30px;
  background-color: #fff3cd;
  font-size: 12px;
  color: #856404;
  border: #ffeeba;
  border-radius: 8px;
}
.left-bottom-bar .content .text-link {
  font-size: 12px;
  color: #856404;
  cursor: pointer;
  text-decoration: underline;
}

.divider-item-block {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 15px;
  margin-bottom: 20px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  padding-bottom: 10px;
}

/* 内容包装器样式 */
.content-wrapper {
  position: relative;
  width: 100%;
  min-height: 100px;
}

/* 卡片视图模式下的内容包装器 */
.content-wrapper.card-view-mode {
  width: 100%;
}
</style>
