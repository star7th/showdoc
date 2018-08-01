<template>
  <div class="hello">
    <Header> </Header>

    <el-container>
          <el-card class="center-card">
            <el-form  status-icon  label-width="0px" class="demo-ruleForm" @keyup.enter.native="onSubmit">
              <h2>重置密码</h2>
              <el-form-item label="" >
                <el-input type="password" auto-complete="off" placeholder="请输入新密码" v-model="new_password"></el-input>
              </el-form-item>


               <el-form-item label="" >
                <el-button type="primary" style="width:100%;" @click="onSubmit" >提交</el-button>
              </el-form-item>

              <el-form-item label="" >
                  <router-link to="/user/login">去登录</router-link>
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
  name: '',
  components : {

  },
  data () {
    return {
      new_password: '',
    }

  },
  methods: {
      onSubmit() {
          //this.$message.success(this.username);
          var that = this ;
          var url = DocConfig.server+'/api/user/resetPasswordByUrl';

          var params = new URLSearchParams();
          params.append('new_password', this.new_password);
          params.append('uid', this.$route.query.uid);
          params.append('email', this.$route.query.email);
          params.append('token', this.$route.query.token);

          that.axios.post(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                let redirect = decodeURIComponent(that.$route.query.redirect || '/item/index');
                that.$router.replace({
                  path: redirect
                });
              }else{
                that.$alert(response.data.error_message);
              }
              
            });
      }
  },
  mounted() {
    /*给body添加类，设置背景色*/
    document.getElementsByTagName("body")[0].className="grey-bg";
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
