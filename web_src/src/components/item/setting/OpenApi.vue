<template>
  <div class="hello">
    <el-form  status-icon  label-width="100px" class="infoForm" >
      <el-form-item label="api_key：" >
        <el-input type="text" auto-complete="off" :readonly="true" v-model="api_key" placeholder="" ></el-input>
      </el-form-item>

      <el-form-item label="api_token" >
        <el-input type="text" auto-complete="off" :readonly="true" v-model="api_token" placeholder="" ></el-input>
      </el-form-item>

       <el-form-item label="" >
        <el-button type="primary" style="width:100%;" @click="resetKey" >{{$t('reset_token')}}</el-button>
      </el-form-item>

    </el-form>

      <p >
         <span v-html="$t('open_api_tips1')"></span>
      </p>
      <p >
        <span v-html="$t('open_api_tips2')"></span>
      </p>
      <p >
        <span v-html="$t('open_api_tips3')"></span>
      </p>
      <p >
        <span v-html="$t('open_api_tips4')"></span>
      </p>
  </div>
</template>

<script>


export default {
  name: 'Login',
  components : {

  },
  data () {
    return {
      api_key:'',
      api_token:''
    }

  },
  methods: {

      get_key_info(){
        var that = this ;
        var url = DocConfig.server+'/api/item/getKey';
        var params = new URLSearchParams();
        params.append('item_id',  that.$route.params.item_id);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              var Info = response.data.data
              that.api_key =  Info.api_key;
              that.api_token =  Info.api_token;
            }else{
              that.$alert(response.data.error_message);
            }
            
          })
          .catch(function (error) {
            console.log(error);
          });
      },
      resetKey() {
          var that = this ;
          var url = DocConfig.server+'/api/item/resetKey';

          var params = new URLSearchParams();
          params.append('item_id',  that.$route.params.item_id);

          that.axios.post(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                that.get_key_info();
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
    this.get_key_info();
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>


.center-card a {
  font-size: 12px;
}

.center-card{
  text-align: left;
  width: 600px;
  height: 500px;
}

.infoForm{
  width:470px;
  margin-left: 20px;
  margin-top: 30px;
}

.goback-btn{
  z-index: 999;
  margin-left: 500px;
}
</style>
