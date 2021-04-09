<template>
  <div class="hello">
    <el-container class="container-narrow">
      <div class="container-thumbnails">
        <div class="search-box-div" v-if="itemList.length > 9">
          <div class="search-box el-input el-input--prefix">
            <input
              autocomplete="off"
              type="text"
              rows="2"
              validateevent="true"
              class="el-input__inner"
              v-model="keyword"
            />
            <span class="el-input__prefix">
              <i class="el-input__icon el-icon-search"></i>
            </span>
          </div>
        </div>

        <ul class="thumbnails" id="item-list" v-if="itemListByKeyword">
          <draggable
            v-model="itemListByKeyword"
            tag="span"
            group="item"
            @end="endMove"
            ghostClass="sortable-chosen"
          >
            <li
              class="text-center"
              v-for="item in itemListByKeyword"
              v-dragging="{ item: item, list: itemListByKeyword, group: 'item' }"
              :key="item.item_id"
            >
              <router-link
                class="thumbnail item-thumbnail"
                :to="'/' +  (item.item_domain ? item.item_domain:item.item_id )"
                :title="item.item_description"
              >
                <!-- 自己创建的话显示项目设置按钮 -->
                <span
                  class="item-setting"
                  @click.prevent="click_item_setting(item.item_id)"
                  :title="$t('item_setting')"
                  v-if="item.creator"
                >
                  <i class="el-icon-setting"></i>
                </span>
                <!-- 如果是加入的项目的话，这里显示退出按钮 -->
                <span
                  class="item-exit"
                  @click.prevent="click_item_exit(item.item_id)"
                  :title="$t('item_exit')"
                  v-if="! item.creator"
                >
                  <i class="el-icon-close"></i>
                </span>
                <p class="my-item">{{item.item_name}}</p>
                <!-- 如果是加密项目的话，这里显示一个加密图标 -->
                <span class="item-private" v-if="item.is_private">
                  <el-tooltip
                    class="item"
                    effect="dark"
                    :content="$t('private_tips')"
                    placement="right"
                  >
                    <i class="el-icon-lock"></i>
                  </el-tooltip>
                </span>
              </router-link>
            </li>
          </draggable>
          <li class="text-center">
            <router-link class="thumbnail item-thumbnail" to="/item/add" title>
              <p class="my-item">
                {{$t('new_item')}}
                <i class="el-icon-plus"></i>
              </p>
            </router-link>
          </li>
        </ul>
      </div>
    </el-container>

    <Footer></Footer>
  </div>
</template>

<style scoped>
.container-narrow {
  margin: 0 auto;
  max-width: 930px;
}
a {
  color: #333;
}
.container-thumbnails {
  margin-top: 30px;
  max-width: 1000px;
}

.my-item {
  margin: 40px 5px;
}

.thumbnails li {
  float: left;
  margin-bottom: 20px;
  margin-left: 20px;
}

.thumbnails li a {
  color: #444;
  font-weight: bold;
  height: 100px;
  width: 180px;
}
.thumbnails li a:hover,
.thumbnails li a:focus {
  border-color: #f2f5e9;
  -webkit-box-shadow: none;
  box-shadow: none;
  text-decoration: none;
  background-color: #f2f5e9;
}

.thumbnail {
  display: block;
  padding: 4px;
  line-height: 20px;
  border: 1px solid #ddd;
  -webkit-border-radius: 4px;
  -moz-border-radius: 4px;
  border-radius: 4px;
  -webkit-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.055);
  -moz-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.055);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.055);
  -webkit-transition: all 0.2s ease-in-out;
  -moz-transition: all 0.2s ease-in-out;
  -o-transition: all 0.2s ease-in-out;
  transition: all 0.2s ease-in-out;
  list-style: none;
  background-color: #ffffff;
}

.item-setting {
  float: right;
  margin-right: 15px;
  margin-top: 5px;
  display: none;
}

.item-exit {
  float: right;
  margin-right: 5px;
  margin-top: 5px;
  display: none;
}
.item-private {
  float: right;
  margin-right: 15px;
  margin-top: -20px;
  display: none;
  cursor: default;
}

.thumbnails li a i {
  color: #777;
  font-weight: bold;
  margin-left: 5px;
}

.item-thumbnail:hover .item-setting {
  display: block;
}
.item-thumbnail:hover .item-exit {
  display: block;
}
.item-thumbnail:hover .item-private {
  display: block;
}

.search-box-div {
  width: 190px;
  margin-left: 60px;
}

.sortable-chosen .item-thumbnail {
  color: #ffffff;
  background-color: #ffffff;
}
</style>

<script>
import draggable from 'vuedraggable'
if (typeof window !== 'undefined') {
  var $s = require('scriptjs')
}
export default {
  components: {
    draggable
  },
  data() {
    return {
      currentDate: new Date(),
      itemList: {},
      isAdmin: false,
      keyword: '',
      lang: '',
      username: ''
    }
  },
  computed: {
    itemListByKeyword: function() {
      if (!this.keyword) {
        return this.itemList
      }
      let itemListByKeyword = []
      for (var i = 0; i < this.itemList.length; i++) {
        if (this.itemList[i]['item_name'].indexOf(this.keyword) > -1) {
          itemListByKeyword.push(this.itemList[i])
        }
      }
      return itemListByKeyword
    }
  },
  methods: {
    get_item_list() {
      this.request('/api/item/myList', {
      }).then((data) => {
        this.itemList = data.data
      })
    },
    
    item_top_class(top) {
      if (top) {
        return 'el-icon-arrow-down'
      }
      return 'el-icon-arrow-up'
    },

    bind_item_even() {
      // 这里偷个懒，直接用jquery来操作DOM。因为老版本的代码就是基于jquery的，所以复制过来稍微改下
      $s(['static/jquery.min.js'], () => {
        // 当鼠标放在项目上时将浮现设置和置顶图标
        $('.item-thumbnail').mouseover(function() {
          $(this)
            .find('.item-setting')
            .show()
          // $(this).find(".item-top").show();
          // $(this).find(".item-down").show();
        })

        // 当鼠标离开项目上时将隐藏设置和置顶图标
        $('.item-thumbnail').mouseout(function() {
          $(this)
            .find('.item-setting')
            .hide()
          $(this)
            .find('.item-top')
            .hide()
          $(this)
            .find('.item-down')
            .hide()
        })
      })
    },

    // 进入项目设置页
    click_item_setting(item_id) {
      this.$router.push({ path: '/item/setting/' + item_id })
    },
    click_item_exit(item_id) {
      var that = this
      this.$confirm(that.$t('confirm_exit_item'), ' ', {
        confirmButtonText: that.$t('confirm'),
        cancelButtonText: that.$t('cancel'),
        type: 'warning'
      }).then(() => {
        var url = DocConfig.server + '/api/item/exitItem'
        var params = new URLSearchParams()
        params.append('item_id', item_id)
        that.axios.post(url, params).then(function(response) {
          if (response.data.error_code === 0) {
            window.location.reload()
          } else {
            that.$alert(response.data.error_message)
          }
        })
      })
    },

    user_info() {
      var that = this
      this.get_user_info(function(response) {
        if (response.data.error_code === 0) {
          if (response.data.data.groupid == 1) {
            that.isAdmin = true
          }
        }
      })
    },
    

    sort_item(data) {
      var that = this
      var url = DocConfig.server + '/api/item/sort'
      var params = new URLSearchParams()
      params.append('data', JSON.stringify(data))
      that.axios.post(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          that.get_item_list()
          // window.location.reload();
        } else {
          that.$alert(response.data.error_message, '', {
            callback: function() {
              window.location.reload()
            }
          })
        }
      })
    },
    exchangeArray(data, oldIndex, newIndex) {
      let tmp = data[oldIndex]
      data.splice(oldIndex, 1)
      data.splice(newIndex, 0, tmp)
      return data
    },
    endMove(evt) {
      let data = {}
      let list = this.exchangeArray(
        this.itemList,
        evt['oldIndex'],
        evt['newIndex']
      )
      this.itemList = []
      this.$nextTick(() => {
        this.itemList = list
      })
      for (var i = 0; i < list.length; i++) {
        let key = list[i]['item_id']
        data[key] = i + 1
      }
      this.sort_item(data)
    },
    script_cron() {
      var url = DocConfig.server + '/api/ScriptCron/run'
      this.axios.get(url)
    },
    checkDb() {
      var url = DocConfig.server + '/api/update/checkDb'
      this.axios.get(url)
    }
  },
  mounted() {
    this.get_item_list()
    this.user_info()
    this.lang = DocConfig.lang
    this.script_cron()
    this.checkDb()
    this.get_user_info(response => {
      if (response.data.error_code === 0) {
        this.username = response.data.data.username
      }
    })
  },
  beforeDestroy() {
    this.$message.closeAll()
  }
}
</script>
