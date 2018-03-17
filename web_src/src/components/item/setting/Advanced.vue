<template>
  <div class="hello">
    <p>
      <el-tooltip :content="$t('attorn_tips')" placement="top-start">
        <el-button class="a_button"  @click="dialogAttornVisible = true" >{{$t('attorn')}}</el-button>
      </el-tooltip>   
    </p>
    <p>
      <el-tooltip :content="$t('archive_tips')" placement="top-start">
        <el-button class="a_button"  @click="dialogArchiveVisible = true" >{{$t('archive')}}</el-button>
      </el-tooltip>   
    </p>

    <p>
      <el-tooltip :content="$t('delete_tips')" placement="top-start">
        <el-button class="a_button"  @click="dialogDeleteVisible = true" >{{$t('delete')}}</el-button>
      </el-tooltip>   
    </p>  

    <el-dialog :visible.sync="dialogAttornVisible" :modal="false">
      <el-form >
          <el-form-item label="" >
            <el-input  :placeholder="$t('attorn_username')" v-model="attornForm.username"></el-input>
          </el-form-item>
          <el-form-item label="" >
            <el-input type="password" :placeholder="$t('input_login_password')"  v-model="attornForm.password" ></el-input>
          </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogAttornVisible = false">{{$t('cancel')}}</el-button>
        <el-button type="primary" @click="attorn" >{{$t('attorn')}}</el-button>
      </div>
    </el-dialog>

    <el-dialog :visible.sync="dialogArchiveVisible" :modal="false">
      <el-form >
          <el-form-item label="" >
            <el-input type="password" :placeholder="$t('input_login_password')" v-model="archiveForm.password"></el-input>
          </el-form-item>
      </el-form>

      <p class="tips">
        {{$t('archive_tips2')}}

      </p>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogArchiveVisible = false">{{$t('cancel')}}</el-button>
        <el-button type="primary" @click="archive" >{{$t('archive')}}</el-button>
      </div>
    </el-dialog>

    <el-dialog :visible.sync="dialogDeleteVisible" :modal="false">
      <el-form >
          <el-form-item label="" >
            <el-input type="password" :placeholder="$t('input_login_password')"  v-model="deleteForm.password">></el-input>
          </el-form-item>
      </el-form>

      <p class="tips">
        <el-tag type="danger">{{$t('delete_tips')}}</el-tag>  
          </p>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogDeleteVisible = false">{{$t('cancel')}}</el-button>
        <el-button type="primary" @click="deleteItem" >{{$t('delete')}}</el-button>
      </div>
    </el-dialog>

  </div>
</template>

<script>


export default {
  name: 'Login',
  components : {

  },
  data () {
    return {
      dialogAttornVisible:false,
      dialogArchiveVisible:false,
      dialogDeleteVisible:false,
      attornForm:{
        username:'',
        password:''
      },
      archiveForm:{
        password:''
      },
      deleteForm:{
        password:''
      }
    }

  },
  methods: {

      deleteItem(){
        var that = this ;
        var url = DocConfig.server+'/api/item/delete';

        var params = new URLSearchParams();
        params.append('item_id',  that.$route.params.item_id);
        params.append('password',this.deleteForm.password );

        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              that.dialogDeleteVisible = false;
              that.$message.success(that.$t("success_jump"));
              setTimeout(function(){
                that.$router.push({path:'/item/index'});
              },2000);
                
            }else{
              that.$alert(response.data.error_message);
            }
            
          })
          .catch(function (error) {
            console.log(error);
          });
      },
      archive() {
        var that = this ;
        var url = DocConfig.server+'/api/item/archive';

        var params = new URLSearchParams();
        params.append('item_id',  that.$route.params.item_id);
        params.append('password',this.archiveForm.password );

        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              that.dialogArchiveVisible = false;
                that.$message.success(that.$t("success_jump"));
                setTimeout(function(){
                  that.$router.push({path:'/item/index'});
                },2000);
                
            }else{
              that.$alert(response.data.error_message);
            }
            
          })
          .catch(function (error) {
            console.log(error);
          });
      },

      attorn(){
        var that = this ;
        var url = DocConfig.server+'/api/item/attorn';

        var params = new URLSearchParams();
        params.append('item_id',  that.$route.params.item_id);
        params.append('username', this.attornForm.username);
        params.append('password',this.attornForm.password );

        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              that.dialogAttornVisible = false;
                that.$message.success(that.$t("success_jump"));
                setTimeout(function(){
                  that.$router.push({path:'/item/index'});
                },2000);
                
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
   
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

.a_button{
  width: 30%;
}

.a_button:first-child{
  margin-top: 30px;
}
</style>
