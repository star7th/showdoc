<template>
  <div class="hello">
    <Header> </Header>

    <el-container>
          <el-card class="center-card">
            <router-link class="goback-btn "to="/item/index">{{$t('goback')}}</router-link>
                <el-form  status-icon  label-width="75px" class="infoForm" v-model="infoForm">
                  <el-form-item :label="$t('username')+':'" >
                      <el-input type="text" auto-complete="off" v-model="infoForm.username" :disabled="true" placeholder="" ></el-input>
                  </el-form-item>
                  <!-- 
                  <el-form-item :label="$t('email')+':'" >
                       <span>{{emailForm.email}}({{emailForm.status}})</span> <a href="javascript:;" @click="dialogEmailFormVisible = true">{{$t("modify")}}</a>
                  </el-form-item>
                  -->
                  <el-form-item :label="$t('name')+':'" >
                      <el-input type="text" auto-complete="off" v-model="infoForm.name" :placeholder="$t('name_tips')" ></el-input>
                  </el-form-item>

                  <el-form-item :label="$t('password')+':'">
                      <a href="javascript:;" @click="dialogPasswordFormVisible = true">{{$t("modify")}}</a>
                  </el-form-item>

                    <el-button type="primary" style="width:100%;" @click="formSubmit" >{{$t('submit')}}</el-button>


                </el-form>
          </el-card>
    </el-container>

    <!-- 修改email弹窗 -->
    <el-dialog :visible.sync="dialogEmailFormVisible" top="10vh" width="300px">
      <el-form class="emailForm">
          <el-form-item label="" >
            <el-input type="text" auto-complete="off" :placeholder="$t('input_email')" v-model="emailForm.email"></el-input>
          </el-form-item>

          <el-form-item label="" >
            <el-input type="password" auto-complete="off" v-model="emailForm.password" :placeholder="$t('input_login_password')"></el-input>
          </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogEmailFormVisible = false">{{$t('cancel')}}</el-button>
        <el-button type="primary" @click="emailFormSubmit" >{{$t('confirm')}}</el-button>
      </div>
    </el-dialog>

    <!-- 修改密码弹窗 -->
    <el-dialog :visible.sync="dialogPasswordFormVisible" top="10vh" width="300px">
      <el-form class="emailForm">
            <el-form-item label="" >
              <el-input type="password" auto-complete="off" :placeholder="$t('old_password')" v-model="passwordForm.password"></el-input>
            </el-form-item>

            <el-form-item label="" >
              <el-input type="password" auto-complete="off" v-model="passwordForm.new_password" :placeholder="$t('new_password')"></el-input>
            </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogPasswordFormVisible = false">{{$t('cancel')}}</el-button>
        <el-button type="primary" @click="passwordFormSubmit" >{{$t('confirm')}}</el-button>
      </div>
    </el-dialog>

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
      infoForm:{
        username:'',
        name:'',
      },
      userInfo:{

      },
      emailForm:{
        email:'',
        status:'',
      },
      passwordForm:{
        password:'',
        new_password:'',
      },
      dialogEmailFormVisible:false,
      dialogPasswordFormVisible:false,
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
              that.infoForm.username = userInfo.username ;
              that.infoForm.name = userInfo.name ;
              if (userInfo.email.length > 0 ) {
                that.emailForm.submit_text =that.$t("modify") ;
                if (userInfo.email_verify > 0 ) {
                  status = that.$t("status_1");

                }else{
                  status = that.$t("status_2");
                }
              }else{
                status = that.$t("status_3");
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
                that.dialogPasswordFormVisible = false;
              }else{
                that.$alert(response.data.error_message);
              }
              
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
                that.dialogEmailFormVisible = false;
                this.get_user_info();
              }else{
                that.$alert(response.data.error_message);
              }
            });
      },
      formSubmit(){
          var that = this ;
          var url = DocConfig.server+'/api/user/updateInfo';

          var params = new URLSearchParams();
          params.append('name', this.infoForm.name);

          that.axios.post(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                that.$message.success(that.$t("modify_success"));
                this.get_user_info();
              }else{
                that.$alert(response.data.error_message);
              }
              
            });
      },
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

.goback-btn{
  z-index: 999;
  margin-left: 500px;
}

.infoForm{
  width: 350px;
  margin: 0 auto ;
  margin-top: 30px;
  text-align: left;
}
</style>
