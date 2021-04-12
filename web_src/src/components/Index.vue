<template>
  <div class="body">
    <!--header-->
    <div class="header">
      <div class="header-wrap">
        <div class="logo">
          <a href="/">
            <img src="static/imgs/Logo.png" />
          </a>
        </div>
        <input type="checkbox" name id="mobile-menu-toggle" value />
        <label class="gh" for="mobile-menu-toggle">
          <span></span>
        </label>
        <div class="nav">
          <ul>
            <li>
              <router-link :to="link">{{ link_text }}</router-link>
            </li>
            <li>
              <a
                target="_blank"
                v-if="lang == 'zh-cn'"
                href="https://www.showdoc.cc/clients"
                >客户端</a
              >
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="hbanner">
      <div class="wrapper">
        <div class="hbanner-txt">
          <h2>
            {{ $t('section_description1_1') }}
            <br />
            <font class="f-blue">{{ $t('section_description1_2') }}</font>
          </h2>
          <div class="btns">
            <a
              href="https://www.showdoc.cc/demo"
              target="_blank"
              class="btn on"
              >{{ $t('demo') }}</a
            >
            <a href="https://www.showdoc.cc/help" target="_blank" class="btn">{{
              $t('more')
            }}</a>
          </div>
        </div>
        <div class="hbanner-imgs"></div>
      </div>
    </div>
    <div class="hrow hrow1">
      <div class="wrapper">
        <div class="img fl">
          <img src="static/imgs/home2.png" />
          <div class="box">
            <img src="static/imgs/home2-img.png" />
          </div>
        </div>
        <div class="txt fr">
          <h2>{{ $t('section_title2') }}</h2>
          <div class="desc">
            <p>
              <img src="static/imgs/Vector.png" />
              {{ $t('section_description2_1') }}
            </p>
            <p>
              <img src="static/imgs/Vector.png" />
              {{ $t('section_description2_2') }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="hrow hrow2">
      <div class="wrapper">
        <div class="img fr">
          <img src="static/imgs/home3.png" />
        </div>
        <div class="txt fl">
          <h2>{{ $t('section_title3') }}</h2>
          <div class="desc">
            <p>
              <img src="static/imgs/Vector1.png" />
              {{ $t('section_description3_1') }}
            </p>
            <p>
              <img src="static/imgs/Vector1.png" />
              {{ $t('section_description3_2') }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="hrow hrow3">
      <div class="wrapper">
        <div class="img fl">
          <img src="static/imgs/home4.png" />
        </div>
        <div class="txt fr">
          <h2>{{ $t('section_title4') }}</h2>
          <div class="desc">
            <p>
              <img src="static/imgs/Vector.png" />
              {{ $t('section_description4_1') }}
            </p>
            <p>
              <img src="static/imgs/Vector.png" />
              {{ $t('section_description4_2') }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="hrow hrow4">
      <div class="wrapper">
        <div class="img fr">
          <img src="static/imgs/home5.png" />
        </div>
        <div class="txt fl">
          <h2>{{ $t('section_title5') }}</h2>
          <div class="desc">
            <p>{{ $t('section_description5') }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="hrow hrow5">
      <div class="wrapper">
        <div class="img fl">
          <img src="static/imgs/home6.png" />
        </div>
        <div class="txt fr">
          <h2>{{ $t('section_title6') }}</h2>
          <div class="desc">
            <p>
              <img src="static/imgs/Vector1.png" />
              {{ $t('section_description6_1') }}
            </p>
            <p>
              <img src="static/imgs/Vector1.png" />
              {{ $t('section_description6_2') }}
            </p>
          </div>
        </div>
      </div>
    </div>
    <div class="hrow hrow6">
      <div class="wrapper">
        <div class="img fr">
          <img src="static/imgs/home7.png" />
        </div>
        <div class="txt fl">
          <h2>{{ $t('section_title7') }}</h2>
          <div class="desc">
            <p>{{ $t('section_description7_1') }}</p>
            <p>{{ $t('section_description7_2') }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="hrow hrow7">
      <div class="wrapper">
        <div class="img fl">
          <img src="static/imgs/home8.png" />
        </div>
        <div class="txt fr">
          <h2>{{ $t('section_title8') }}</h2>
          <div class="desc">
            <p>{{ $t('section_description8_1') }}</p>
            <p>{{ $t('section_description8_2') }}</p>
          </div>
        </div>
      </div>
    </div>
    <div class="hfoot">
      <div class="wrapper">
        <h2>{{ $t('section_description9') }}</h2>
        <router-link to="/user/login" class="btn">{{
          $t('section_title9')
        }}</router-link>
      </div>
      <div class="copyright">
        <a href="http://www.beian.miit.gov.cn/"></a>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Index',
  data() {
    return {
      height: '',
      link: '',
      link_text: '',
      lang: ''
    }
  },
  methods: {
    getHeight() {
      var winHeight
      if (window.innerHeight) {
        winHeight = window.innerHeight
      } else if (document.body && document.body.clientHeight) {
        winHeight = document.body.clientHeight
      }
      this.height = winHeight + 'px'
    },
    homePageSetting() {
      var url = DocConfig.server + '/api/common/homePageSetting'
      this.axios.post(url, this.form).then(response => {
        if (response.data.error_code === 0) {
          if (response.data.data.home_page == 2) {
            // 跳转到登录页面
            this.$router.replace({
              path: '/user/login'
            })
          }
          if (
            response.data.data.home_page == 3 &&
            response.data.data.home_item
          ) {
            // 跳转到指定项目
            this.$router.replace({
              path: '/' + response.data.data.home_item
            })
          }
        }
      })
    }
  },
  mounted() {
    var that = this
    this.lang = DocConfig.lang
    this.getHeight()
    this.homePageSetting()
    that.link = '/user/login'
    that.link_text = that.$t('index_login_or_register')
    this.get_user_info(function(response) {
      if (response.data.error_code === 0) {
        that.link = '/item/index'
        that.link_text = that.$t('my_item')
      }
    })
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped src="@/../static/css/qietu.css"></style>
<style scoped src="@/../static/css/style.css"></style>
<style scoped src="@/../static/css/responsive.css"></style>
