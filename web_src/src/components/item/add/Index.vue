<template>
  <div class="hello">
    <Header></Header>

    <el-container>
      <el-card class="center-card">
        <template>
          <el-button type="text" @click="goback" class="goback-btn">
            <i class="el-icon-back"></i>
          </el-button>

          <el-tabs value="first" type="card">
            <el-tab-pane :label="$t('new_item')" name="first">
              <Regular></Regular>
            </el-tab-pane>

            <el-tab-pane :label="$t('copy_item')" name="third">
              <Copy></Copy>
            </el-tab-pane>

            <el-tab-pane :label="$t('import_file')" name="four">
              <Import></Import>
            </el-tab-pane>

            <el-tab-pane :label="$t('auto_item')" name="five">
              <OpenApi></OpenApi>
            </el-tab-pane>
          </el-tabs>
        </template>
      </el-card>
    </el-container>

    <Footer></Footer>
  </div>
</template>

<script>
import Regular from '@/components/item/add/Regular'
import Copy from '@/components/item/add/Copy'
import OpenApi from '@/components/item/add/OpenApi'
import Import from '@/components/item/add/Import'

export default {
  name: 'Login',
  components: {
    Regular,
    Copy,
    OpenApi,
    Import
  },
  data() {
    return {
      userInfo: {}
    }
  },
  methods: {
    get_item_info() {
      var that = this
      var url = DocConfig.server + '/api/item/detail'
      var params = new URLSearchParams()
      params.append('item_id', that.$route.params.item_id)
      that.axios
        .post(url, params)
        .then(function(response) {
          if (response.data.error_code === 0) {
            var Info = response.data.data
            that.infoForm = Info
          } else {
            that.$alert(response.data.error_message)
          }
        })
        .catch(function(error) {
          console.log(error)
        })
    },
    goback() {
      this.$router.go(-1)
    }
  },

  mounted() {},
  beforeDestroy() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.center-card a {
  font-size: 12px;
}

.center-card {
  text-align: center;
  width: 463px;
  min-height: 600px;
  max-height: 800px;
}

.goback-btn {
  font-size: 18px;
  margin-right: 800px;
  margin-bottom: 15px;
}
</style>
