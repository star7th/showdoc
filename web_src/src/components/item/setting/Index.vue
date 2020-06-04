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
            <el-tab-pane :label="$t('base_info')" name="first">
              <Info></Info>
            </el-tab-pane>

            <el-tab-pane :label="$t('member_manage')" name="second">
              <Member></Member>
            </el-tab-pane>

            <el-tab-pane :label="$t('advance_setting')" name="third">
              <Advanced></Advanced>
            </el-tab-pane>

            <el-tab-pane :label="$t('open_api')" name="four">
              <OpenApi></OpenApi>
            </el-tab-pane>

            <el-tab-pane :label="$t('recycle')" name="five">
              <Recycle></Recycle>
            </el-tab-pane>
          </el-tabs>
        </template>
      </el-card>
    </el-container>

    <Footer></Footer>
  </div>
</template>

<script>
import Info from '@/components/item/setting/Info'
import Member from '@/components/item/setting/Member'
import Advanced from '@/components/item/setting/Advanced'
import OpenApi from '@/components/item/setting/OpenApi'
import Recycle from '@/components/item/setting/Recycle'

export default {
  name: 'Login',
  components: {
    Info,
    Member,
    Advanced,
    OpenApi,
    Recycle
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
  width: 555px;
  min-height: 600px;
  max-height: 800px;
}

.infoForm {
  width: 350px;
  margin-left: 60px;
  margin-top: 30px;
}

.goback-btn {
  z-index: 999;
  font-size: 18px;
  margin-right: 800px;
  margin-bottom: 5px;
}
</style>
