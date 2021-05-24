<template>
  <div class="hello">
    <el-form ref="form" :model="form" label-width="150px">
      <el-form-item :label="$t('register_open')">
        <el-switch v-model="form.register_open"></el-switch>
      </el-form-item>
      <!-- 待支持
      <el-form-item label="所有人可以新建项目">
        <el-switch v-model="form.register_open"></el-switch>
      </el-form-item>
      -->
      <el-form-item :label="$t('home_page')">
        <el-select v-model="form.home_page" :placeholder="$t('please_choose')">
          <el-option :label="$t('full_page')" value="1"></el-option>
          <el-option :label="$t('login_page')" value="2"></el-option>
          <el-option :label="$t('jump_to_an_item')" value="3"></el-option>
          <!-- <el-option label="展示全站项目" value="4"></el-option> -->
        </el-select>
      </el-form-item>

      <el-form-item :label="$t('jump_to_item')" v-show="form.home_page == 3">
        <el-select v-model="form.home_item" :placeholder="$t('please_choose')">
          <el-option
            v-for="item in itemList"
            :label="item.item_name"
            :value="item.item_id"
            :key="item.item_id"
          ></el-option>
        </el-select>
      </el-form-item>
      <el-form-item :label="$t('history_version_count')">
        <el-input
          v-model="form.history_version_count"
          class="form-el"
        ></el-input>
        <el-tooltip
          class="item"
          effect="dark"
          :content="$t('history_version_count_content')"
          placement="top"
        >
          <i class="el-icon-question"></i>
        </el-tooltip>
      </el-form-item>

      <el-form-item :label="$t('oss_open')">
        <el-switch v-model="form.oss_open"></el-switch>
      </el-form-item>

      <div v-if="form.oss_open" style="margin-left:50px">
        <el-form-item :label="$t('oss_server')">
          <el-select v-model="form.oss_setting.oss_type">
            <el-option :label="$t('aliyun')" value="aliyun"></el-option>
            <el-option :label="$t('qiniu')" value="qiniu"></el-option>
            <el-option :label="$t('qcloud')" value="qcloud"></el-option>
          </el-select>
        </el-form-item>

        <el-form-item label="key" v-if="form.oss_setting.oss_type != 'qcloud'">
          <el-input v-model="form.oss_setting.key" class="form-el"></el-input>
        </el-form-item>

        <el-form-item
          label="secret"
          v-if="form.oss_setting.oss_type != 'qcloud'"
        >
          <el-input
            v-model="form.oss_setting.secret"
            class="form-el"
          ></el-input>
        </el-form-item>

        <el-form-item
          label="endpoint"
          v-if="form.oss_setting.oss_type == 'aliyun'"
        >
          <el-input
            v-model="form.oss_setting.endpoint"
            class="form-el"
          ></el-input>
        </el-form-item>

        <el-form-item
          label="region"
          v-if="form.oss_setting.oss_type == 'qcloud'"
        >
          <el-input
            v-model="form.oss_setting.region"
            class="form-el"
          ></el-input>
        </el-form-item>
        <el-form-item
          label="secretId"
          v-if="form.oss_setting.oss_type == 'qcloud'"
        >
          <el-input
            v-model="form.oss_setting.secretId"
            class="form-el"
          ></el-input>
        </el-form-item>
        <el-form-item
          label="secretKey"
          v-if="form.oss_setting.oss_type == 'qcloud'"
        >
          <el-input
            v-model="form.oss_setting.secretKey"
            class="form-el"
          ></el-input>
        </el-form-item>

        <el-form-item label="bucket">
          <el-input
            v-model="form.oss_setting.bucket"
            class="form-el"
          ></el-input>
        </el-form-item>

        <el-form-item :label="$t('oss_domain')">
          <el-select v-model="form.oss_setting.protocol" style="width:100px;">
            <el-option label="http://" value="http"></el-option>
            <el-option label="https://" value="https"></el-option>
          </el-select>
          <el-input
            v-model="form.oss_setting.domain"
            class="form-el"
          ></el-input>
        </el-form-item>
      </div>

      <br />
      <el-form-item>
        <el-button type="primary" @click="onSubmit">{{ $t('save') }}</el-button>
        <el-button>{{ $t('cancel') }}</el-button>
      </el-form-item>
    </el-form>
  </div>
</template>

<style scoped>
.form-el {
  width: 230px;
}
</style>

<script>
export default {
  data() {
    return {
      form: {
        register_open: true,
        home_page: '1',
        home_item: '',
        oss_open: false,
        oss_setting: {
          oss_type: 'aliyun',
          key: '',
          secret: '',
          endpoint: '',
          bucket: '',
          protocol: 'http',
          domain: '',
          region: '',
          secretId: '',
          secretKey: ''
        },
        history_version_count: 20
      },
      itemList: []
    }
  },
  methods: {
    onSubmit() {
      var url = DocConfig.server + '/api/adminSetting/saveConfig'
      this.axios.post(url, this.form).then(response => {
        if (response.data.error_code === 0) {
          this.$alert(this.$t('success'))
        } else {
          this.$alert(response.data.error_message)
        }
      })
    },
    loadConfig() {
      var url = DocConfig.server + '/api/adminSetting/loadConfig'
      this.axios.post(url, this.form).then(response => {
        if (response.data.error_code === 0) {
          if (response.data.data.length === 0) {
            return
          }
          this.form.register_open = response.data.data.register_open > 0
          this.form.history_version_count = response.data.data
            .history_version_count
            ? response.data.data.history_version_count
            : this.form.history_version_count
          this.form.oss_open = response.data.data.oss_open > 0
          this.form.home_page =
            response.data.data.home_page > 0 ? response.data.data.home_page : 1
          this.form.home_item =
            response.data.data.home_item > 0 ? response.data.data.home_item : ''
          this.form.oss_setting = response.data.data.oss_setting
            ? response.data.data.oss_setting
            : this.form.oss_setting
          this.form.oss_setting.region = this.form.oss_setting.region
            ? this.form.oss_setting.region
            : ''
          this.form.oss_setting.secretId = this.form.oss_setting.secretId
            ? this.form.oss_setting.secretId
            : ''
          this.form.oss_setting.secretKey = this.form.oss_setting.secretKey
            ? this.form.oss_setting.secretKey
            : ''
        } else {
          this.$alert(response.data.error_message)
        }
      })
    },
    get_item_list() {
      var that = this
      var url = DocConfig.server + '/api/adminItem/getList'

      var params = new URLSearchParams()
      params.append('page', 1)
      params.append('count', 1000)
      that.axios.post(url, params).then(function(response) {
        if (response.data.error_code === 0) {
          // that.$message.success("加载成功");
          var json = response.data.data
          that.itemList = json.items
        } else {
          that.$alert(response.data.error_message)
        }
      })
    }
  },
  mounted() {
    this.get_item_list()
    this.loadConfig()
  },
  beforeDestroy() {
    this.$message.closeAll()
  }
}
</script>
