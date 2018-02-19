<template>
  <div class="hello">
    <Header> </Header>

    <el-container>
          <el-card class="center-card">
          <template>
            <el-button type="text" class="goback-btn " ><router-link to="/item/index">{{$t('goback')}}</router-link></el-button>
            <el-tabs  value="first" type="card">
            
              <el-tab-pane :label="$t('modify_password')" name="first">

                <el-form  status-icon  label-width="0px" class="passwordForm" v-model="passwordForm">
                  <el-form-item label="" >
                    <el-input type="text" auto-complete="off" v-model="passwordForm.username" placeholder="" :disabled="true"></el-input>
                  </el-form-item>
                  <el-form-item label="" >
                    <el-input type="password" auto-complete="off" :placeholder="$t('old_password')" v-model="passwordForm.password"></el-input>
                  </el-form-item>

                  <el-form-item label="" >
                    <el-input type="password" auto-complete="off" v-model="passwordForm.new_password" :placeholder="$t('new_password')"></el-input>
                  </el-form-item>

                   <el-form-item label="" >
                    <el-button type="primary" style="width:100%;" @click="passwordFormSubmit" >{{$t('submit')}}</el-button>
                  </el-form-item>

                </el-form>

            </el-tab-pane>


            </el-tabs>
          </template>
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
      passwordForm:{
        username:'',
        password:'',
        new_password:''
      },
      emailForm:{
        status:'',
        email:'',
        password:'',
        submit_text:''
      },
      userInfo:{

      }
    }

  },
  methods: {

      get_user_info(){
        var that = this ;
        var url = DocConfig.server+'/api/user/info';
        var params = new URLSearchParams();
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              var userInfo = response.data.data
              that.userInfo =  userInfo;
              that.passwordForm.username = userInfo.username;
              that.emailForm.email = userInfo.email ;
              var status = that.$t("status")+':';
              if (userInfo.email.length > 0 ) {
                that.emailForm.submit_text =that.$t("modify") ;
                if (userInfo.email_verify > 0 ) {
                  status += that.$t("status_1");

                }else{
                  status += that.$t("status_2");
                }
              }else{
                status += that.$t("status_3");
                that.emailForm.submit_text =that.$t("binding") ;
              }
              that.emailForm.status = status ;
            }else{
              that.$alert(response.data.error_message);
            }
            
          })
          .catch(function (error) {
            console.log(error);
          });
      },
      passwordFormSubmit() {
          var that = this ;
          var url = DocConfig.server+'/api/user/resetPassword';

          var params = new URLSearchParams();
          params.append('new_password', this.passwordForm.new_password);
          params.append('password', this.passwordForm.password);

          that.axios.post(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                that.$message.success(that.$t("modify_success"));
              }else{
                that.$alert(response.data.error_message);
              }
              
            })
            .catch(function (error) {
              console.log(error);
            });
      },
      emailFormSubmit(){
          var that = this ;
          var url = DocConfig.server+'/api/user/updateEmail';

          var params = new URLSearchParams();
          params.append('email', this.emailForm.email);
          params.append('password', this.emailForm.password);

          that.axios.post(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                that.$alert(that.$t("update_email_success"));
                this.get_user_info();
              }else{
                that.$alert(response.data.error_message);
              }
              
            })
            .catch(function (error) {
              console.log(error);
            });
      }
  },

  mounted(){
    
    this.get_user_info();
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
  width: 600px;
  height: 500px;
}

.passwordForm,.emailForm{
  width:300px;
  margin: 0 auto ;
  margin-top: 50px;
}

.goback-btn{
  z-index: 999;
  margin-left: 500px;
}
</style>
