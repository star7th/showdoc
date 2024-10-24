<template>
  <div class="item-list">
    <draggable
      v-model="itemList"
      tag="span"
      group="item"
      @end="endMove"
      ghostClass="sortable-chosen"
    >
      <div
        v-loading="loading"
        v-for="item in itemList"
        :key="item.item_id"
        @click="toOneItem(item)"
        class="item-list-one"
      >
        <div class="item-list-one-block">
          <div class="left   float-left">
            <i v-if="item.item_type == '2'" class="item-icon fas fa-file"></i>
            <i
              v-else-if="item.item_type == '4'"
              class="item-icon fas fa-table"
            ></i>
            <i v-else class="item-icon fas fa-notes"></i>
            {{ item.item_name }}
          </div>
          <div class="right show-more  float-right" @click.stop="() => {}">
            <el-dropdown :show-timeout="0" trigger="hover">
              <span class="el-dropdown-link">
                <i class="item-icon-more fas fa-ellipsis"></i>
              </span>
              <el-dropdown-menu slot="dropdown">
                <el-dropdown-item @click.native="toOneItem(item)">
                  <i class="mr-2 fas fa-right-to-bracket"></i>
                  {{ $t('open_item') }}
                </el-dropdown-item>
                <el-dropdown-item
                  @click.native="
                    opItemRow = item
                    showShare = true
                  "
                >
                  <i class="mr-2 fas fa-share-nodes"></i>
                  {{ $t('share') }}
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="item.is_star <= 0"
                  @click.native="clickStar(item)"
                >
                  <i class="mr-2 far fa-star"></i>
                  {{ $t('star_item') }}
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="item.is_star > 0"
                  @click.native="clickStar(item)"
                >
                  <i class="mr-2 fas fa-star"></i>
                  {{ $t('unstar_item') }}
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="!item.manage"
                  @click.native="exitItem(item.item_id)"
                >
                  <i class="mr-2 fas fa-trash"></i>
                  {{ $t('item_exit') }}
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="item.manage"
                  divided
                  @click.native="
                    opItemRow = item
                    showItemUpdate = true
                  "
                >
                  <i class="mr-2 fas fa-edit"></i>
                  {{ $t('update_base_info') }}
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="item.manage"
                  @click.native="
                    opItemRow = item
                    showMember = true
                  "
                >
                  <i class="mr-2 fal fa-users"></i>
                  {{ $t('member_manage') }}
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="item.manage"
                  @click.native="
                    opItemRow = item
                    showOpenApi = true
                  "
                >
                  <i class="mr-2 fas fa-plug"></i>
                  {{ $t('open_api') }}
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="item.manage"
                  @click.native="
                    opItemRow = item
                    showRecycle = true
                  "
                >
                  <i class="mr-2 fas fa-trash"></i>
                  {{ $t('recycle') }}
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="item.manage"
                  divided
                  @click.native="
                    opItemRow = item
                    showAttorn = true
                  "
                >
                  <i class="mr-2 fas fa-recycle"></i>
                  {{ $t('attorn') }}
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="item.manage"
                  @click.native="
                    opItemRow = item
                    showCopy = true
                  "
                >
                  <i class="mr-2 fas fa-copy"></i>
                  {{ $t('copy') }}
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="item.manage"
                  @click.native="
                    opItemRow = item
                    showArchive = true
                  "
                >
                  <i class="mr-2 far fa-box-archive"></i>
                  {{ $t('archive') }}
                </el-dropdown-item>
                <el-dropdown-item
                  v-if="item.manage"
                  @click.native="
                    opItemRow = item
                    showDelete = true
                  "
                >
                  <i class="mr-2 far fa-trash-can"></i>
                  {{ $t('delete') }}
                </el-dropdown-item>
              </el-dropdown-menu>
            </el-dropdown>
          </div>
        </div>
        <div class="item-list-one-block-bg"></div>
      </div>
    </draggable>

    <!-- 分享项目 -->
    <Share
      v-if="showShare"
      :callback="
        () => {
          showShare = false
        }
      "
      :item_info="{
        item_domain: opItemRow.item_domain,
        item_id: opItemRow.item_id
      }"
    >
    </Share>

    <!-- 更新项目信息的弹窗 -->
    <ItemUpdate
      v-if="showItemUpdate"
      :callback="
        () => {
          showItemUpdate = false
          getItemList()
        }
      "
      :item_id="opItemRow.item_id"
    >
    </ItemUpdate>

    <!-- 项目成员&团队的弹窗 -->
    <Member
      v-if="showMember"
      :callback="
        () => {
          showMember = false
        }
      "
      :item_id="opItemRow.item_id"
    >
    </Member>

    <!-- 开放api弹窗 -->
    <OpenApi
      v-if="showOpenApi"
      :callback="
        () => {
          showOpenApi = false
        }
      "
      :item_id="opItemRow.item_id"
    >
    </OpenApi>

    <!-- 回收站的弹窗 -->
    <Recycle
      v-if="showRecycle"
      :callback="
        () => {
          showRecycle = false
        }
      "
      :item_id="opItemRow.item_id"
    >
    </Recycle>

    <!-- 归档项目 -->
    <Archive
      v-if="showArchive"
      :callback="
        () => {
          showArchive = false
        }
      "
      :item_id="opItemRow.item_id"
    >
    </Archive>

    <!-- 转让项目 -->
    <Attorn
      v-if="showAttorn"
      :callback="
        () => {
          showAttorn = false
          getItemList()
        }
      "
      :item_id="opItemRow.item_id"
    >
    </Attorn>

    <!-- 复制项目 -->
    <Copy
      :item_id="opItemRow.item_id"
      v-if="showCopy"
      :callback="
        () => {
          showCopy = false
          getItemList()
        }
      "
    >
    </Copy>

    <!-- 删除项目 -->
    <Delete
      v-if="showDelete"
      :callback="
        () => {
          showDelete = false
          getItemList()
        }
      "
      :item_id="opItemRow.item_id"
    >
    </Delete>
  </div>
</template>

<script>
import draggable from 'vuedraggable'
import ItemUpdate from '@/components/item/add/Basic'
import Member from '@/components/item/setting/Member'
import OpenApi from '@/components/item/setting/OpenApi'
import Recycle from '@/components/item/setting/Recycle'
import Archive from '@/components/item/setting/Archive'
import Attorn from '@/components/item/setting/Attorn'
import Delete from '@/components/item/setting/Delete'
import Share from '@/components/item/home/Share'
import Copy from '@/components/item/add/Copy'

export default {
  name: 'ItemList',
  components: {
    draggable,
    ItemUpdate,
    Member,
    OpenApi,
    Recycle,
    Archive,
    Attorn,
    Delete,
    Share,
    Copy
  },
  props: {
    callback: {
      type: Function,
      required: false,
      default: () => {}
    },
    getItemList: {
      type: Function,
      required: false,
      default: () => {}
    },
    itemList: {
      type: Array,
      required: false,
      default: []
    },
    itemGroupId: {
      type: Number,
      required: false,
      default: 0
    }
  },
  data() {
    return {
      loading: false,
      showShare: false,
      opItemRow: { item_domain: '', item_id: 0 },
      showItemUpdate: false,
      showMember: false,
      showOpenApi: false,
      showRecycle: false,
      showArchive: false,
      showAttorn: false,
      showDelete: false,
      showCopy: false
    }
  },

  methods: {
    toOneItem(item) {
      const to = '/' + (item.item_domain ? item.item_domain : item.item_id)
      this.$router.push({ path: to })
    },
    // 星标或者取消星标
    clickStar(row) {
      const is_star = row.is_star
      const item_id = row.item_id
      // 如果is_star > 0 ,即已经标星了，那么本次点击就是 取消星标 的意思
      if (is_star > 0) {
        this.request('/api/item/unstar', {
          item_id: item_id
        }).then(data => {
          for (let index = 0; index < this.itemList.length; index++) {
            const element = this.itemList[index]
            if (element.item_id == item_id) {
              this.itemList[index]['is_star'] = 0
            }
          }
          if (this.itemGroupId === -1) {
            // 如果当前用户正在查看星标项目选项卡，那么，取消星标后，应该重刷新项目列表，以便消息该非星标项目
            this.getItemList()
          }
          this.$message(this.$t('op_success'))
        })
      } else {
        this.request('/api/item/star', {
          item_id: item_id
        }).then(data => {
          for (let index = 0; index < this.itemList.length; index++) {
            const element = this.itemList[index]
            if (element.item_id == item_id) {
              this.itemList[index]['is_star'] = 1
            }
          }
          this.$message(this.$t('op_success'))
        })
      }
    },
    sortItem(data) {
      this.request(
        '/api/item/sort',
        {
          data: JSON.stringify(data),
          item_group_id: this.itemGroupId
        },
        'post',
        false
      ).then(data => {
        if (data.error_code === 0) {
          this.getItemList()
          // window.location.reload();
        } else {
          this.$alert(data.error_message, '', {
            callback: function() {
              window.location.reload()
            }
          })
        }
      })
    },
    endMove(evt) {
      let data = {}
      for (var i = 0; i < this.itemList.length; i++) {
        let key = this.itemList[i]['item_id']
        data[key] = i + 1
      }
      this.sortItem(data)
    },
    // 退出项目
    exitItem(item_id) {
      this.$confirm(this.$t('confirm_exit_item'), this.$t('warning'), {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/item/exitItem', {
          item_id: item_id
        }).then(response => {
          this.getItemList()
        })
      })
    }
  },

  mounted() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.el-dropdown-link,
a {
  color: #343a40;
}
.item-list-one {
  margin-top: 10px;
  margin-bottom: 10px;
  cursor: pointer;
}

.item-list-one-block {
  width: 600px;
  height: 60px;
  background-color: white;
  color: #343a40;
  border-radius: 12px;
  box-shadow: 0 0 2px #0000001a;
  float: left;
  opacity: 1;
  position: relative;
  bottom: 5px;
  right: 5px;
}

.item-list-one-block-bg {
  width: 600px;
  height: 60px;
  background-color: white;
  color: #343a40;
  border-radius: 12px;
  box-shadow: 0 0 2px #0000001a;
}
.item-list-one .left {
  position: relative;
  top: 50%;
  transform: translateY(-50%);
  padding-left: 20px;
}
.item-list-one .right {
  position: relative;
  top: 50%;
  transform: translateY(-50%);
  padding-right: 20px;
}

.item-list-one .show-more {
  display: none;
}

.item-list-one:hover .show-more {
  display: block;
}

.item-list-one .item-icon {
  margin-right: 10px;
  color: rgba(0, 0, 0, 0.3);
  font-size: 16px;
}

.item-list-one .item-icon-more {
  color: #343a40;
  font-size: 16px;
}
</style>
