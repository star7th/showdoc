<template>
  <div class="hello">
    <Header> </Header>

    <el-container>
          <el-card class="center-card">
            <el-form  status-icon  label-width="0px" class="demo-ruleForm" @keyup.enter.native="onSubmit">
              <h2>{{$t("login")}}</h2>
              <el-form-item label="" >
                <el-input type="text" auto-complete="off" :placeholder="$t('username_description')" v-model="username"></el-input>
              </el-form-item>

              <el-form-item label="" >
                <el-input type="password" auto-complete="off" v-model="password" :placeholder="$t('password')"></el-input>
              </el-form-item>

              <el-form-item label="" v-if="show_v_code">
                <el-input type="text" auto-complete="off" v-model="v_code" :placeholder="$t('verification_code')"></el-input>
                <img v-bind:src="v_code_img"  class="v_code_img" v-on:click="change_v_code_img" >

              </el-form-item>

               <el-form-item label="" >
                <el-button type="primary" style="width:100%;" @click="onSubmit" >{{$t("login")}}</el-button>
              </el-form-item>

              <el-form-item label="" >
                  <router-link to="/user/register">{{$t("register_new_account")}}</router-link>
                  &nbsp;&nbsp;&nbsp;

              </el-form-item>
            </el-form>
          </el-card>
    </el-container>

    <Footer> </Footer>
    
  </div>
</template>

<script>


export default {
  name: 'Login',
  components : {

  },
  data () {
    return {
      username: '',
      password: '',
      v_code: '',
      v_code_img:DocConfig.server+'/api/common/verify',
      show_v_code:false,
      is_show_alert:false
    }

  },
  methods: {
      onSubmit() {
          if (this.is_show_alert) { return ;};
          //this.$message.success(this.username);
          var that = this ;
          var url = DocConfig.server+'/api/user/login';
          var params = new URLSearchParams();
          params.append('username', this.username);
          params.append('password', this.password);
          params.append('v_code', this.v_code);

          that.axios.post(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                //that.$message.success("登录成功");
                let redirect = decodeURIComponent(that.$route.query.redirect || '/item/index');
                that.$router.replace({
                  path: redirect
                });
              }else{
                if (response.data.error_code === 10206 || response.data.error_code === 10210) {
                  that.show_v_code = true ;
                  that.change_v_code_img() ;
                };
                that.is_show_alert = true ;
                that.$alert(response.data.error_message,{callback:function(){
                 setTimeout(function(){
                    that.is_show_alert = false;
                 },500);
                 
                }});
              }
              
            });
      },
      change_v_code_img(){
        var rand = '&rand='+Math.random();
        this.v_code_img += rand ;
      },
      script_cron(){
        var url = DocConfig.server+'/api/ScriptCron/run';
        this.axios.get(url);
      }

  },
  mounted() {
    var that = this ;
    /*给body添加类，设置背景色*/
    document.getElementsByTagName("body")[0].className="grey-bg";
    this.get_user_info(function(response){
      if (response.data.error_code === 0 ) {
        let redirect = decodeURIComponent(that.$route.query.redirect || '/item/index');
        that.$router.replace({
          path: redirect
        });
      }
    });

    this.script_cron();
  },
  beforeDestroy(){
    /*去掉添加的背景色*/
    document.body.removeAttribute("class","grey-bg");
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

.center-card a {
  font-size: 12px;
}

.center-card{
  text-align: center;
}

.v_code_img{
  margin-top: 20px;
}

</style>
