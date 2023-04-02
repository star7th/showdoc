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

      <el-form-item v-show="$lang == 'zh-cn'" label="备案号">
        <el-input v-model="form.beian" class="form-el"></el-input>
        <el-tooltip
          effect="dark"
          content="设置后会展示在网站首页最下方"
          placement="top"
        >
          <i class="el-icon-question"></i>
        </el-tooltip>
      </el-form-item>

      <el-form-item :label="$t('history_version_count')">
        <el-input
          v-model="form.history_version_count"
          class="form-el"
        ></el-input>
        <el-tooltip
          effect="dark"
          :content="$t('history_version_count_content')"
          placement="top"
        >
          <i class="el-icon-question"></i>
        </el-tooltip>
      </el-form-item>

      <el-form-item :label="$t('watermark')">
        <el-switch v-model="form.show_watermark"></el-switch>
        <el-tooltip
          effect="dark"
          :content="$t('watermark_tips')"
          placement="top"
        >
          <i class="el-icon-question"></i>
        </el-tooltip>
      </el-form-item>
      <el-form-item :label="$t('site_url')">
        <el-input
          v-model="form.site_url"
          class="form-el"
          placeholder="https://www.your-site.com"
        ></el-input>
        <el-tooltip
          effect="dark"
          :content="$t('site_url_tips')"
          placement="top"
        >
          <i class="el-icon-question"></i>
        </el-tooltip>
      </el-form-item>
      <el-form-item v-show="$lang == 'zh-cn'" label="AI助手认证KEY">
        <el-input
          v-model="form.open_api_key"
          class="form-el"
          placeholder=""
        ></el-input>

        <el-tooltip effect="dark" content="点击查看填写说明" placement="top">
          <i
            class="el-icon-question cursor-pointer "
            @click="
              toOutLink(
                'https://www.showdoc.com.cn/p/30dd0637811cd5c690ffd547f3c46889'
              )
            "
          ></i>
        </el-tooltip>
      </el-form-item>
      <el-form-item v-show="$lang == 'zh-cn'" label="AI助手代理HOST">
        <el-input
          v-model="form.open_api_host"
          class="form-el"
          placeholder="可选"
        ></el-input>

        <el-tooltip effect="dark" content="点击查看填写说明" placement="top">
          <i
            class="el-icon-question cursor-pointer "
            @click="toOutLink('https://github.com/star7th/showdoc/issues/1904')"
          ></i>
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
            <el-option :label="$t('s3_storage')" value="s3_storage"></el-option>
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
          v-if="
            form.oss_setting.oss_type == 'aliyun' ||
              form.oss_setting.oss_type == 's3_storage'
          "
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
        <el-button @click="loadConfig">{{ $t('cancel') }}</el-button>
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
        history_version_count: 20,
        beian: '',
        show_watermark: false,
        site_url: '',
        open_api_key: '',
        open_api_host: ''
      },
      itemList: []
    }
  },
  methods: {
    onSubmit() {
      this.request(
        '/api/adminSetting/saveConfig',
        this.form,
        'post',
        true,
        'json'
      ).then(data => {
        this.$alert(this.$t('success'))
      })
    },
    loadConfig() {
      this.request('/api/adminSetting/loadConfig', {}).then(data => {
        if (data.data.length === 0) {
          return
        }
        this.form.register_open = data.data.register_open > 0
        this.form.history_version_count = data.data.history_version_count
          ? data.data.history_version_count
          : this.form.history_version_count
        this.form.oss_open = data.data.oss_open > 0
        this.form.home_page = data.data.home_page > 0 ? data.data.home_page : 1
        this.form.home_item = data.data.home_item > 0 ? data.data.home_item : ''
        this.form.oss_setting = data.data.oss_setting
          ? data.data.oss_setting
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
        this.form.beian = data.data.beian ? data.data.beian : ''
        this.form.show_watermark = data.data.show_watermark > 0
        this.form.site_url = data.data.site_url ? data.data.site_url : ''
        this.form.open_api_key = data.data.open_api_key
          ? data.data.open_api_key
          : ''
        this.form.open_api_host = data.data.open_api_host
          ? data.data.open_api_host
          : ''
      })
    },
    getItemList() {
      this.request('/api/adminItem/getList', {
        page: 1,
        count: 100
      }).then(data => {
        this.itemList = data.data.items
      })
    }
  },
  mounted() {
    this.getItemList()
    this.loadConfig()
  },
  beforeDestroy() {
    this.$message.closeAll()
  }
}
</script>
