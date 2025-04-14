<!-- 附件 -->
<template>
  <div class="">
    <SDialog
      :title="$t('share')"
      :onCancel="callback"
      :showCancel="false"
      :showOk="false"
      :onOK="callback"
      width="600px"
    >
      <p>
        {{ $t('item_page_address') }} :
        <code>{{ share_page_link }}</code>
        <i
          class="el-icon-document-copy cursor-pointer"
          v-clipboard:copy="share_page_link"
          v-clipboard:success="onCopy"
        ></i>
      </p>
      <p v-if="false" style="border-bottom: 1px solid #eee;">
        <img
          id="qr-page-link"
          style="width:114px;height:114px;"
          :src="qr_page_link"
        />
      </p>

      <div v-show="item_info.item_edit">
        <el-checkbox
          v-model="isCreateSiglePage"
          @change="checkCreateSiglePage"
          >{{ $t('create_sigle_page') }}</el-checkbox
        >

        <p v-if="isCreateSiglePage">
          {{ $t('single_page_address') }} :
          <code>{{ share_single_link }}</code>
          <i
            class="el-icon-document-copy cursor-pointer"
            v-clipboard:copy="share_single_link"
            v-clipboard:success="onCopy"
          ></i>
        </p>

        <div v-if="isCreateSiglePage">
          <p>{{ $t('expire_time') }}:</p>
          <el-radio-group v-model="expireTime" @change="onExpireTimeChange">
            <el-radio :label="0">{{ $t('permanent') }}</el-radio>
            <el-radio :label="1">{{ $t('one_day') }}</el-radio>
            <el-radio :label="7">{{ $t('seven_days') }}</el-radio>
            <el-radio :label="30">{{ $t('one_month') }}</el-radio>
            <el-radio :label="180">{{ $t('half_year') }}</el-radio>
          </el-radio-group>
        </div>

        <p></p>
        <p class="tips-text">{{ $t('create_sigle_page_tips') }}</p>
      </div>
    </SDialog>
  </div>
</template>

<style></style>

<script>
export default {
  props: {
    callback: () => {},
    page_info: '',
    item_info: ''
  },
  data() {
    return {
      qr_page_link: '#',
      qr_single_link: '#',
      share_page_link: '',
      share_single_link: '',
      copyText1: '',
      copyText2: '',
      isCreateSiglePage: false,
      page_id: '',
      expireTime: 0 // 默认永久有效
    }
  },
  components: {},
  computed: {},
  methods: {
    sharePage() {
      var page_id = this.page_id > 0 ? this.page_id : 0
      let path = this.item_info.item_domain
        ? this.item_info.item_domain
        : this.item_info.item_id
      this.share_page_link = this.getRootPath() + '#/' + path + '/' + page_id
      // this.share_single_link= this.getRootPath()+"/page/"+page_id ;
      this.qr_page_link =
        DocConfig.server +
        '/api/common/qrcode&size=3&url=' +
        encodeURIComponent(this.share_page_link)
      // this.qr_single_link = DocConfig.server +'/api/common/qrcode&size=3&url='+encodeURIComponent(this.share_single_link);
      this.showShare = true
      this.copyText1 =
        this.item_info.item_name +
        ' - ' +
        this.page_info.page_title +
        '\r\n' +
        this.share_page_link
      this.copyText2 =
        this.page_info.page_title + '\r\n' + this.share_single_link
    },
    onCopy() {
      this.$message(this.$t('copy_success'))
    },
    checkCreateSiglePage(newvalue) {
      if (newvalue) {
        this.createSiglePage()
      } else {
        this.$confirm(this.$t('cancelSingle'), ' ', {
          confirmButtonText: this.$t('cancelSingleYes'),
          cancelButtonText: this.$t('cancelSingleNo'),
          type: 'warning'
        }).then(
          () => {
            this.createSiglePage()
          },
          () => {
            this.isCreateSiglePage = true
          }
        )
      }
    },
    onExpireTimeChange() {
      // 更新分享链接有效期设置
      this.createSiglePage()
    },
    createSiglePage() {
      var page_id = this.page_id > 0 ? this.page_id : 0
      this.request('/api/page/createSinglePage', {
        page_id: page_id,
        isCreateSiglePage: this.isCreateSiglePage,
        expire_days: this.expireTime
      }).then(data => {
        var unique_key = data.data.unique_key
        if (unique_key) {
          this.share_single_link = this.getRootPath() + '#/p/' + unique_key
        } else {
          this.share_single_link = ''
        }
      })
    }
  },
  mounted() {
    this.page_id = this.page_info.page_id
    this.sharePage()
    if (this.page_info.unique_key) {
      this.isCreateSiglePage = true
      this.share_single_link =
        this.getRootPath() + '#/p/' + this.page_info.unique_key
      // 获取单页链接的有效期设置
      this.request('/api/page/infoByKey', {
        unique_key: this.page_info.unique_key
      }).then(data => {
        if (data.data && data.data.expire_time) {
          // 根据过期时间计算天数设置
          const now = Math.floor(Date.now() / 1000) // 当前时间戳（秒）
          const expireTime = data.data.expire_time

          if (expireTime > 0) {
            const diffDays = Math.round((expireTime - now) / (24 * 60 * 60))

            // 根据剩余天数匹配最接近的选项
            if (diffDays <= 1) {
              this.expireTime = 1 // 一天
            } else if (diffDays <= 7) {
              this.expireTime = 7 // 七天
            } else if (diffDays <= 30) {
              this.expireTime = 30 // 一个月
            } else if (diffDays <= 180) {
              this.expireTime = 180 // 半年
            } else {
              this.expireTime = 0 // 如果不匹配任何选项，设为永久
            }
          } else {
            this.expireTime = 0 // 如果expire_time为0，表示永久有效
          }
        }
      })
    }
  }
}
</script>
