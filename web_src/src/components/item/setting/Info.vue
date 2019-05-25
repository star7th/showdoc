<template>
  <div class="hello">
    <el-form  status-icon  label-width="100px" class="infoForm" v-model="infoForm">
      <el-form-item :label="$t('item_name')+':'" >
        <el-input type="text" auto-complete="off" v-model="infoForm.item_name" placeholder="" ></el-input>
      </el-form-item>

      <el-form-item :label="$t('item_description')+':'" >
        <el-input type="text" auto-complete="off" v-model="infoForm.item_description" placeholder="" ></el-input>
      </el-form-item>

      <el-form-item label="" >
        <el-radio v-model="isOpenItem" :label="true">{{$t('Open_item')}}</el-radio>
        <el-radio v-model="isOpenItem" :label="false">{{$t('private_item')}}</el-radio>
      </el-form-item>

      <el-form-item :label="$t('visit_password')+':'" v-show="!isOpenItem">
            <el-input type="password" auto-complete="off"  v-model="infoForm.password"></el-input>
      </el-form-item>

       <el-form-item label="" >
        <el-button type="primary" style="width:100%;" @click="FormSubmit" >{{$t('submit')}}</el-button>
      </el-form-item>

    </el-form>
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

      },
      isOpenItem:true,
    }

  },
  methods: {

      get_item_info(){
        var that = this ;
        var url = DocConfig.server+'/api/item/detail';
        var params = new URLSearchParams();
        params.append('item_id',  that.$route.params.item_id);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              var Info = response.data.data
              if (Info.password) {
                that.isOpenItem = false;
              };
              that.infoForm =  Info;
            }else{
              that.$alert(response.data.error_message);
            }
            
          })
          .catch(function (error) {
            console.log(error);
          });
      },
      FormSubmit() {
          var that = this ;
          var url = DocConfig.server+'/api/item/update';
          if (!this.isOpenItem && !this.infoForm.password) {
            that.$alert(that.$t("private_item_passwrod"));
            return false;
          };
          if (this.isOpenItem) {
            this.infoForm.password = '';
          };
          var params = new URLSearchParams();
          params.append('item_id',  that.$route.params.item_id);
          params.append('item_name', this.infoForm.item_name);
          params.append('item_description', this.infoForm.item_description);
          params.append('item_domain', this.infoForm.item_domain);
          params.append('password', this.infoForm.password);

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
  },

  mounted(){
    this.get_item_info();
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

.infoForm{
  width:350px;
  margin-left: 60px;
  margin-top: 30px;
}

.goback-btn{
  z-index: 999;
  margin-left: 500px;
}
</style>
