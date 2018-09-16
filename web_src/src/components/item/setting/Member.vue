<template>
  <div class="hello">
    <el-button  type="text" class="add-member" @click="dialogFormVisible = true">{{$t('add_member')}}</el-button>
     <el-table align="left"
          :data="members"
           height="300"
          style="width: 100%">
          <el-table-column
            prop="username"
            :label="$t('username')"
            width="100">
          </el-table-column>
          <el-table-column
            prop="addtime"
            :label="$t('add_time')"
            width="160">
          </el-table-column>
          <el-table-column
            prop="member_group"
            :label="$t('authority')">
          </el-table-column>
          <el-table-column
            prop=""
            :label="$t('operation')">
            <template slot-scope="scope">
              <el-button @click="delete_member(scope.row.item_member_id)" type="text" size="small">{{$t('delete')}}</el-button>
            </template>
          </el-table-column>
        </el-table>

    <el-dialog :visible.sync="dialogFormVisible" :modal="false" top="10vh">
      <el-form >
          <el-form-item label="" >
            <el-autocomplete  
            :placeholder="$t('input_target_member')"  
            v-model="MyForm.username" 
            :fetch-suggestions="querySearch" 
            ></el-autocomplete>
          </el-form-item>
          <el-form-item label="" class="readonly-checkbox" >
            <el-checkbox v-model="MyForm.is_readonly">{{$t('readonly')}}</el-checkbox>
          </el-form-item>
      </el-form>

      <p class="tips">
        {{$t('member_authority_tips')}}
      </p>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{$t('cancel')}}</el-button>
        <el-button type="primary" @click="MyFormSubmit" >{{$t('confirm')}}</el-button>
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
      MyForm:{
        username:'',
        is_readonly:false
      },
      members:[],
      dialogFormVisible:false,
      all_user:[]
    }

  },
  methods: {

      get_members(){
        var that = this ;
        var url = DocConfig.server+'/api/member/getList';
        var params = new URLSearchParams();
        params.append('item_id',  that.$route.params.item_id);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              var Info = response.data.data
              that.members =  Info;
            }else{
              that.$alert(response.data.error_message);
            }
            
          });
      },
      MyFormSubmit() {
          var that = this ;
          var url = DocConfig.server+'/api/member/save';

          var params = new URLSearchParams();
          params.append('item_id',  that.$route.params.item_id);
          params.append('username', this.MyForm.username);
          var member_group_id = 1 ;
          if (this.MyForm.is_readonly) {
              member_group_id = 0 
          };
          params.append('member_group_id',member_group_id );

          that.axios.post(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                that.dialogFormVisible = false;
                that.get_members() ;
                that.MyForm.username = '';
                that.MyForm.is_readonly = false;
              }else{
                that.$alert(response.data.error_message);
              }
              
            })
            .catch(function (error) {
              console.log(error);
            });
      },

      delete_member(item_member_id){
          var that = this ;
          var url = DocConfig.server+'/api/member/delete';

          this.$confirm(that.$t('confirm_delete'), ' ', {
            confirmButtonText: that.$t('confirm'),
            cancelButtonText: that.$t('cancel'),
            type: 'warning'
          }).then(() => {
            var params = new URLSearchParams();
            params.append('item_id',  that.$route.params.item_id);
            params.append('item_member_id', item_member_id);

            that.axios.post(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                that.get_members() ;
              }else{
                that.$alert(response.data.error_message);
              }
            }); 
          })

      },
      get_all_user(){
        var that = this ;
        var url = DocConfig.server+'/api/user/allUser';
        that.axios.post(url)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              that.all_user = response.data.data
            }else{
              that.$alert(response.data.error_message);
            }
            
          });
      },
      querySearch(queryString, cb) {
        var all_user = this.all_user;
        var results = queryString ? all_user.filter(this.createFilter(queryString)) : all_user;
        // 调用 callback 返回建议列表的数据
        cb(results);
      },
      createFilter(queryString) {
        return (all_user) => {
          return (all_user.value.toLowerCase().indexOf(queryString.toLowerCase()) === 0);
        } 
      }
  },

  mounted(){
    this.get_members();
    this.get_all_user();
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

.hello{
  text-align: left;
}

.add-member{
  margin-left: 10px;
}

.tips{
  font-size: 12px;
  margin-bottom: 0px;
  margin-top: 0px;
}
</style>
