<template>
  <div class="item-card-list">
    <div class="card-container">
      <draggable
        v-model="itemList"
        tag="div"
        group="item"
        @end="endMove"
        ghostClass="sortable-chosen"
        class="draggable-container"
      >
        <el-col
          :xs="24"
          :sm="12"
          :md="8"
          :lg="6"
          v-for="item in itemList"
          :key="item.item_id"
          class="card-item-col"
        >
          <div
            class="item-card"
            @click="toOneItem(item)"
            :class="{ 'is-starred': item.is_star > 0 }"
          >
            <div class="item-card-content">
              <div class="item-card-title">{{ item.item_name }}</div>
            </div>
            <div class="item-card-actions" @click.stop>
              <el-dropdown trigger="hover" placement="bottom-end">
                <span class="el-dropdown-link">
                  <i class="fas fa-ellipsis"></i>
                </span>
                <el-dropdown-menu slot="dropdown">
                  <el-dropdown-item @click.native.stop="toOneItem(item)">
                    <i class="mr-2 fas fa-right-to-bracket"></i>
                    {{ $t('open_item') }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    @click.native.stop="
                      opItemRow = item
                      showShare = true
                    "
                  >
                    <i class="mr-2 fas fa-share-nodes"></i>
                    {{ $t('share') }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    v-if="item.is_star <= 0"
                    @click.native.stop="clickStar(item)"
                  >
                    <i class="mr-2 far fa-star"></i>
                    {{ $t('star_item') }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    v-if="item.is_star > 0"
                    @click.native.stop="clickStar(item)"
                  >
                    <i class="mr-2 fas fa-star"></i>
                    {{ $t('unstar_item') }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    v-if="!item.manage"
                    @click.native.stop="exitItem(item.item_id)"
                  >
                    <i class="mr-2 fas fa-trash"></i>
                    {{ $t('item_exit') }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    v-if="item.manage"
                    divided
                    @click.native.stop="
                      opItemRow = item
                      showItemUpdate = true
                    "
                  >
                    <i class="mr-2 fas fa-edit"></i>
                    {{ $t('update_base_info') }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    v-if="item.manage"
                    @click.native.stop="
                      opItemRow = item
                      showMember = true
                    "
                  >
                    <i class="mr-2 far fa-users"></i>
                    {{ $t('member_manage') }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    v-if="item.manage"
                    @click.native.stop="
                      opItemRow = item
                      showOpenApi = true
                    "
                  >
                    <i class="mr-2 fas fa-plug"></i>
                    {{ $t('open_api') }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    v-if="item.manage"
                    @click.native.stop="
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
                    @click.native.stop="
                      opItemRow = item
                      showAttorn = true
                    "
                  >
                    <i class="mr-2 fas fa-recycle"></i>
                    {{ $t('attorn') }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    v-if="item.manage"
                    @click.native.stop="
                      opItemRow = item
                      showCopy = true
                    "
                  >
                    <i class="mr-2 fas fa-copy"></i>
                    {{ $t('copy') }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    v-if="item.manage"
                    @click.native.stop="
                      opItemRow = item
                      showArchive = true
                    "
                  >
                    <i class="mr-2 fas fa-box-archive"></i>
                    {{ $t('archive') }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    v-if="item.manage"
                    @click.native.stop="
                      opItemRow = item
                      showDelete = true
                    "
                  >
                    <i class="mr-2 fas fa-trash-can"></i>
                    {{ $t('delete') }}
                  </el-dropdown-item>
                </el-dropdown-menu>
              </el-dropdown>
            </div>
          </div>
        </el-col>
      </draggable>
    </div>

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

    <!-- 转让项目 -->
    <Attorn
      v-if="showAttorn"
      :callback="
        () => {
          showAttorn = false
        }
      "
      :item_id="opItemRow.item_id"
    >
    </Attorn>

    <!-- 复制项目 -->
    <Copy
      v-if="showCopy"
      :callback="
        () => {
          showCopy = false
        }
      "
      :item_id="opItemRow.item_id"
    >
    </Copy>

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
import Share from '@/components/item/home/Share'
import ItemUpdate from '@/components/item/add/Basic'
import Member from '@/components/item/setting/Member'
import OpenApi from '@/components/item/setting/OpenApi'
import Recycle from '@/components/item/setting/Recycle'
import Attorn from '@/components/item/setting/Attorn'
import Copy from '@/components/item/add/Copy'
import Delete from '@/components/item/setting/Delete'
import Archive from '@/components/item/setting/Archive'

export default {
  name: 'ItemCardList',
  props: {
    itemList: Array,
    getItemList: Function,
    itemGroupId: Number
  },
  components: {
    draggable,
    Share,
    ItemUpdate,
    Member,
    OpenApi,
    Recycle,
    Attorn,
    Copy,
    Delete,
    Archive
  },
  data() {
    return {
      showItemUpdate: false,
      showShare: false,
      showMember: false,
      showOpenApi: false,
      showRecycle: false,
      showAttorn: false,
      showCopy: false,
      showDelete: false,
      showArchive: false,
      loading: false,
      opItemRow: {}
    }
  },
  methods: {
    toOneItem(item) {
      const to = '/' + (item.item_domain ? item.item_domain : item.item_id)
      this.$router.push({ path: to })
    },
    clickStar(item) {
      this.request('/api/item/star', {
        item_id: item.item_id
      }).then(data => {
        if (item.is_star > 0) {
          item.is_star = 0
        } else {
          item.is_star = 1
        }
      })
    },
    exitItem(item_id) {
      this.$confirm(this.$t('confirm_exit'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/item/exitItem', {
          item_id: item_id
        }).then(data => {
          this.getItemList()
        })
      })
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
    }
  }
}
</script>

<style scoped>
.item-card-list {
  margin-bottom: 30px;
  width: 100%;
  overflow: hidden;
}

.card-container {
  margin-top: 20px;
  width: 100%;
}

.draggable-container {
  display: flex;
  flex-wrap: wrap;
  margin: 0 -7.5px;
  width: calc(100% + 15px);
}

.card-item-col {
  padding: 0 7.5px;
  box-sizing: border-box;
}

.el-row {
  width: 100%;
  margin-left: 0 !important;
  margin-right: 0 !important;
}

.item-card {
  background: #fff;
  border-radius: 4px;
  box-shadow: 0 0 2px rgba(0, 0, 0, 0.1);
  margin-bottom: 15px;
  padding: 15px;
  position: relative;
  transition: all 0.2s ease;
  cursor: pointer;
  height: 100px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(0, 0, 0, 0.03);
}

.item-card:hover {
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.item-card-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  text-align: center;
  width: 100%;
  padding-top: 10px;
}

.item-card-title {
  font-size: 14px;
  font-weight: normal;
  line-height: 1.4;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  max-height: 40px;
  padding: 0 5px;
}

.item-card-actions {
  position: absolute;
  top: 8px;
  right: 8px;
  display: none;
  align-items: center;
  z-index: 2;
}

.item-card:hover .item-card-actions {
  display: flex;
}

.el-dropdown-link {
  cursor: pointer;
  color: #343a40;
  padding: 4px;
  font-size: 16px;
  border-radius: 2px;
}

.el-dropdown-link i {
  font-size: 16px;
}

.is-starred {
  position: relative;
}

/* 简化收藏标记 */
.is-starred::before {
  content: none;
}

/* 拖拽时的样式 */
.sortable-chosen {
  background: #f9f9f9;
  opacity: 0.8;
}
</style>
