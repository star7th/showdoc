<template>
  <div class="hello">
    <div class="header">
      <div @click="reload" class="logo float-left mt-4 ml-10  cursor-pointer">
        <div>
          <div class="inline-block align-middle">
            <img src="static/logo/b_64.png" />
          </div>
          <div class="inline-block align-middle">
            <div class="v3-font-size-lg font-bold">ShowDoc</div>
            <div class=" v3-color-aux">{{ $t('home_logo_title') }}</div>
          </div>
        </div>
      </div>
      <HeaderRight :isAdmin="isAdmin"></HeaderRight>
    </div>
    <div class="container">
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
              <i class="el-icon-s-grid mr-1"></i>{{ $t('all_items') }}
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
              <i class="el-icon-star-on mr-1"></i>{{ $t('star_items') }}
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
              ><i class="el-icon-plus mr-1"></i
            ></el-tooltip>
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
              <i class="mr-1 font-black">#</i> {{ one.group_name }}
            </div>
          </div>
        </div>
      </div>
      <div class="right-side align-top  inline-block">
        <div class="search-box-div">
          <div class="search-box el-input el-input--prefix">
            <el-input
              autocomplete="off"
              type="text"
              class="search-input"
              validateevent="true"
              :clearable="true"
              v-model="keyword"
            />
            <span class="el-input__prefix">
              <i class="el-input__icon el-icon-search"></i>
            </span>
          </div>
        </div>
        <div class="divider-item-block  mt-3 mb-3">
          <div class="divider-text">{{ selectedGroupName }}</div>
        </div>

        <!-- 项目列表组件  -->
        <ItemListCom
          v-if="!showSearch"
          :itemList="itemList"
          :getItemList="getItemList"
          :itemGroupId="itemGroupId"
        >
        </ItemListCom>

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
        <itemAdd
          :callback="
            () => {
              getItemList()
            }
          "
        ></itemAdd>
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
import { getUserInfo } from '@/models/user.js'
export default {
  components: {
    draggable,
    itemGroupCom,
    HeaderRight,
    itemAdd,
    Search,
    ItemListCom,
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
      showItemGroupCom: false
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
      getUserInfo(function(response) {
        if (response.data.error_code === 0) {
          that.username = response.data.data.username
          if (response.data.data.groupid == 1) {
            that.isAdmin = true
          }
        }
      })
    }
  },
  mounted() {
    const deaultItemGroupId = localStorage.getItem('deaultItemGroupId')
    if (deaultItemGroupId === null) {
      this.getItemList()
      this.itemGroupId = 0
    } else {
      this.itemGroupId = parseInt(deaultItemGroupId)
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

.el-dropdown-link,
a {
  color: #343a40;
}

.container {
  width: 850px;
  margin: 0 auto;
}
.left-side {
  width: 220px;
  padding-top: 40px;
  border-right: 1px solid rgba(0, 0, 0, 0.05);
  min-height: calc(100vh - 150px);
}

.item-one-block {
  height: 40px;
  position: relative;
  border-radius: 8px;
  cursor: pointer;
}

.item-one-block:hover {
  background-color: white;
}
.item-one-block-active {
  background-color: white;
}

.item-one-block-content {
  padding-left: 5px;
  position: absolute;
  top: 50%;
  transform: translate(0, -50%);
}

.divider-text {
  font-size: 9px;
  color: #9b9b9b;
  display: inline;
  margin-left: 10px;
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
  padding-top: 50px;
  padding-left: 20px;
}
.search-input {
  width: 600px;
}
.empty {
  margin: 5% auto;
  width: 400px;
  text-align: center;
  color: #000;
  margin-top: 30%;
  opacity: 0.25;
}
.empty .icon {
  font-size: 50px;
}

.empty .text {
  font-size: 11px;
}
</style>
