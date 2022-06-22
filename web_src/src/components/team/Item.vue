<template>
  <div class="hello">
    <Header></Header>

    <el-container>
      <el-card class="center-card">
        <el-button type="text" class="goback-btn" @click="goback"
          ><i class="el-icon-back"></i>&nbsp;{{ $t('goback') }}</el-button
        >
        <el-button
          v-if="team_manage"
          type="text"
          class="add-cat"
          @click="addTeamItem"
          ><i class="el-icon-plus"></i>&nbsp;{{ $t('binding_item') }}</el-button
        >
        <el-table align="left" :data="list" height="400" style="width: 100%">
          <el-table-column
            prop="item_name"
            :label="$t('item_name')"
          ></el-table-column>
          <el-table-column
            prop="addtime"
            :label="$t('Join_time')"
          ></el-table-column>

          <el-table-column prop width="210" :label="$t('operation')">
            <template slot-scope="scope">
              <router-link :to="'/' + scope.row.item_id" target="_blank">{{
                $t('check_item')
              }}</router-link>

              <el-button
                v-if="team_manage"
                @click="getTeamItemMember(scope.row.item_id)"
                type="text"
                size="small"
                >{{ $t('member_authority') }}</el-button
              >

              <el-button
                v-if="team_manage"
                @click="deleteTeamItem(scope.row.id)"
                type="text"
                size="small"
                >{{ $t('unassign') }}</el-button
              >
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <el-dialog
        :visible.sync="dialogFormVisible"
        width="300px"
        :close-on-click-modal="false"
      >
        <el-form>
          <el-select
            multiple
            v-model="MyForm.item_id"
            :placeholder="$t('please_choose')"
          >
            <el-option
              v-for="item in itemList"
              :key="item.item_id"
              :label="item.item_name"
              :value="item.item_id"
            ></el-option>
          </el-select>
        </el-form>
        <br />
        <router-link to="/item/index" target="_blank">{{
          $t('go_to_new_an_item')
        }}</router-link>
        <div slot="footer" class="dialog-footer">
          <el-button @click="dialogFormVisible = false">{{
            $t('cancel')
          }}</el-button>
          <el-button type="primary" @click="myFormSubmit">{{
            $t('confirm')
          }}</el-button>
        </div>
      </el-dialog>

      <!-- 成员权限弹窗 -->
      <el-dialog
        :visible.sync="dialogFormTeamMemberVisible"
        top="10vh"
        :title="$t('adjust_member_authority')"
        :close-on-click-modal="false"
      >
        <p>
          <el-button type="text" @click="setAllMemberRead"
            >&nbsp;{{ $t('all_member_read') }}</el-button
          >
        </p>
        <el-table
          align="left"
          :empty-text="$t('team_member_empty_tips')"
          :data="teamItemMembers"
          style="width: 100%"
        >
          <el-table-column
            prop="member_username"
            :label="$t('username')"
          ></el-table-column>
          <el-table-column
            prop="member_group_id"
            :label="$t('authority')"
            width="120"
          >
            <template slot-scope="scope">
              <el-select
                size="mini"
                v-model="scope.row.member_group_id"
                @change="changeTeamItemMemberGroup($event, scope.row.id)"
                :placeholder="$t('please_choose')"
              >
                <el-option
                  v-for="item in authorityOptions"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                ></el-option>
              </el-select>
            </template>
          </el-table-column>
          <el-table-column prop="cat_id" :label="$t('catalog')" width="130">
            <template slot-scope="scope">
              <el-select
                size="mini"
                v-if="scope.row.member_group_id <= 1"
                v-model="scope.row.cat_id"
                @change="changeTeamItemMemberCat($event, scope.row.id)"
                :placeholder="$t('please_choose')"
              >
                <el-option
                  v-for="item in catalogs"
                  :key="item.cat_id"
                  :label="item.cat_name"
                  :value="item.cat_id"
                ></el-option>
              </el-select>
            </template>
          </el-table-column>
          <el-table-column
            prop="addtime"
            :label="$t('add_time')"
          ></el-table-column>
        </el-table>
        <br />
        <p class="tips">{{ $t('team_member_authority_tips') }}</p>
        <div slot="footer" class="dialog-footer">
          <el-button @click="dialogFormTeamMemberVisible = false">{{
            $t('close')
          }}</el-button>
        </div>
      </el-dialog>
    </el-container>

    <Footer></Footer>
  </div>
</template>

<script>
export default {
  components: {},
  data() {
    return {
      MyForm: {
        item_id: ''
      },
      list: [],
      dialogFormVisible: false,
      team_id: '',
      itemList: [],
      teamItemMembers: [],
      dialogFormTeamMemberVisible: false,
      authorityOptions: [
        {
          label: this.$t('edit_member'),
          value: '1'
        },
        {
          label: this.$t('readonly_member'),
          value: '0'
        },
        {
          label: this.$t('item_admin'),
          value: '2'
        }
      ],
      catalogs: [],
      team_manage: 1
    }
  },
  methods: {
    geList() {
      this.request('/api/teamItem/getListByTeam', {
        team_id: this.team_id
      }).then(data => {
        this.list = data.data
      })
    },
    getItemList() {
      this.request('/api/item/myList', { original: 1 }).then(data => {
        this.itemList = data.data
      })
    },
    myFormSubmit() {
      this.request('/api/teamItem/save', {
        team_id: this.team_id,
        item_id: this.MyForm.item_id
      }).then(data => {
        this.dialogFormVisible = false
        this.geList()
        this.MyForm = {}
      })
    },

    deleteTeamItem(id) {
      this.$confirm(this.$t('confirm_unassign'), ' ', {
        confirmButtonText: this.$t('confirm'),
        cancelButtonText: this.$t('cancel'),
        type: 'warning'
      }).then(() => {
        this.request('/api/teamItem/delete', {
          id: id
        }).then(data => {
          this.geList()
        })
      })
    },
    addTeamItem() {
      this.MyForm = []
      this.dialogFormVisible = true
    },
    goback() {
      this.$router.push({ path: '/team/index' })
    },
    getTeamItemMember(item_id) {
      this.dialogFormTeamMemberVisible = true
      this.getCatalog(item_id)
      this.request('/api/teamItemMember/getList', {
        item_id: item_id,
        team_id: this.team_id
      }).then(data => {
        this.teamItemMembers = data.data
      })
    },
    changeTeamItemMemberGroup(member_group_id, id, showMsg = true) {
      this.request('/api/teamItemMember/save', {
        member_group_id: member_group_id,
        id: id
      }).then(data => {
        if (showMsg) this.$message(this.$t('auth_success'))
      })
    },
    changeTeamItemMemberCat(cat_id, id) {
      var that = this
      var url = DocConfig.server + '/api/teamItemMember/save'

      var params = new URLSearchParams()
      params.append('cat_id', cat_id)
      params.append('id', id)

      that.axios.post(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          that.$message(that.$t('cat_success'))
        } else {
          that.$alert(response.data.error_message)
        }
      })
    },
    getCatalog(item_id) {
      this.request('/api/catalog/catListGroup', {
        item_id: item_id
      }).then(data => {
        var Info = data.data
        Info.unshift({
          cat_id: '0',
          cat_name: this.$t('all_cat')
        })
        this.catalogs = Info
      })
    },
    // 一键全部设置为只读
    setAllMemberRead() {
      this.teamItemMembers.forEach(element => {
        this.changeTeamItemMemberGroup(0, element.id, false)
        setTimeout(() => {
          this.getTeamItemMember(element.item_id)
        }, 500)
      })
    }
  },

  mounted() {
    this.team_id = this.$route.params.team_id
    this.team_manage = this.$route.query.team_manage > 0 ? 1 : 0
    this.geList()
    this.getItemList()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.hello {
  text-align: left;
}

.add-cat {
  float: right;
  margin-right: 15px;
  font-size: 14px;
}

.center-card {
  text-align: left;
  width: 800px;
  height: 600px;
}

.goback-btn {
  z-index: 999;
  font-size: 14px;
}
</style>

<!-- 全局css -->
<style>
.el-table .success-row {
  background: #f0f9eb;
}
</style>
