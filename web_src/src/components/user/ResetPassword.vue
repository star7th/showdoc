<template>
  <div class="hello">
    <Header> </Header>

    <el-container>
          <el-card class="center-card">
            <el-form  status-icon  label-width="0px" class="demo-ruleForm" @keyup.enter.native="onSubmit">
              <h2>重置密码</h2>
              <el-form-item label="" >
                <el-input type="text" auto-complete="off" placeholder="绑定的邮箱" v-model="email"></el-input>
              </el-form-item>

              <el-form-item label="" >
                <el-input type="text" auto-complete="off" v-model="v_code" placeholder="验证码"></el-input>
                <img v-bind:src="v_code_img" class="v_code_img"   v-on:click="change_v_code_img" >

              </el-form-item>

               <el-form-item label="" >
                <el-button type="primary" style="width:100%;" @click="onSubmit" >提交</el-button>
              </el-form-item>

              <el-form-item label="" >
                  <router-link to="/user/login">想起密码了？去登录</router-link>
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
      email: '',
      v_code: '',
      v_code_img:DocConfig.server+'/api/common/verify'
    }

  },
  methods: {
      onSubmit() {
          //this.$message.success(this.username);
          var that = this ;
          var url = DocConfig.server+'/api/user/resetPasswordEmail';

          var params = new URLSearchParams();
          params.append('email', this.email);
          params.append('v_code', this.v_code);

          that.axios.post(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                that.$alert("已成功发送重置密码邮件到你的邮箱中。请登录并查看邮件");
              }else{
                that.$alert(response.data.error_message);
              }
              
            });
      },
      change_v_code_img(){
        var rand = '&rand='+Math.random();
        this.v_code_img += rand ;
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
