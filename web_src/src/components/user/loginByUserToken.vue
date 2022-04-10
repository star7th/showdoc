<template>
  <div class="hello">
    <Header></Header>

    <Footer></Footer>
  </div>
</template>

<script>
export default {
  name: '',
  components: {},
  data() {
    return {}
  },
  mounted() {
    const user_token = this.$route.query.user_token
    // 获取用户信息
    this.request(
      '/api/user/info',
      {
        user_token: user_token,
        redirect_login: false
      },
      'post',
      false
    ).then(data => {
      if (data.error_code === 0) {
        // 写登陆信息
        const userinfo = data.data
        userinfo.user_token = user_token
        localStorage.setItem('userinfo', JSON.stringify(userinfo))
        // 设置cookie
        var d = new Date()
        d.setTime(d.getTime() + 180 * 24 * 60 * 60 * 1000)
        var expires = 'expires=' + d.toGMTString()
        document.cookie =
          'cookie_token=' +
          user_token +
          ';  samesite=strict; path=/; ' +
          expires
      }

      // 无论是否登录成功，都跳转
      let redirect = decodeURIComponent(
        this.$route.query.redirect_uri || '/item/index'
      )
      this.$router.replace({
        path: redirect
      })
    })
  },
  beforeDestroy() {}
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
