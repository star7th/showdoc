<template>
  <div class="hello">
    <p class="tips">{{ $t('recycle_tips') }}</p>
    <!-- 页面列表 -->
    <el-table
      align="left"
      class="recycle-table"
      v-if="lists.length > 0"
      :data="lists"
      style="width: 100%"
    >
      <el-table-column
        prop="page_title"
        :label="$t('page_title')"
      ></el-table-column>
      <el-table-column
        prop="del_by_username"
        :label="$t('deleter')"
      ></el-table-column>
      <el-table-column
        prop="del_time"
        :label="$t('del_time')"
      ></el-table-column>
      <el-table-column prop :label="$t('operation')">
        <template slot-scope="scope">
          <el-button
            @click="recover(scope.row.page_id)"
            type="text"
            size="small"
            >{{ $t('recover') }}</el-button
          >
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
export default {
  name: 'Login',
  components: {},
  data() {
    return {
      MyForm: {
        username: '',
        is_readonly: false
      },
      MyForm2: {
        team_id: ''
      },
      lists: []
    }
  },
  methods: {
    get_list() {
      var that = this
      var url = DocConfig.server + '/api/recycle/getList'
      var params = new URLSearchParams()
      params.append('item_id', that.$route.params.item_id)
      that.axios.post(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          var Info = response.data.data
          that.lists = Info
        } else {
          that.$alert(response.data.error_message)
        }
      })
    },
    recover(page_id) {
      var that = this
      var url = DocConfig.server + '/api/recycle/recover'

      this.$confirm(this.$t('recover_tips'), ' ', {
        confirmButtonText: that.$t('confirm'),
        cancelButtonText: that.$t('cancel'),
        type: 'warning'
      }).then(() => {
        var params = new URLSearchParams()
        params.append('item_id', that.$route.params.item_id)
        params.append('page_id', page_id)

        that.axios.post(url, params).then(function(response) {
          if (response.data.error_code === 0) {
            that.get_list()
          } else {
            that.$alert(response.data.error_message)
          }
        })
      })
    }
  },

  mounted() {
    this.get_list()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.hello {
  text-align: left;
}
.tips {
  margin-left: 10px;
  color: #9ea1a6;
}
.recycle-table {
  max-height: 400px;
  overflow: auto;
}
</style>
