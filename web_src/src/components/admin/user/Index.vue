<template>

<div class="hello">
<el-form :inline="true"  class="demo-form-inline">
  <el-form-item label="">
    <el-input v-model="username" placeholder="用户名"></el-input>
  </el-form-item>
<!--   <el-form-item label="活动区域">
    <el-select v-model="formInline.region" placeholder="活动区域">
      <el-option label="区域一" value="shanghai"></el-option>
      <el-option label="区域二" value="beijing"></el-option>
    </el-select>
  </el-form-item> -->
  <el-form-item>
    <el-button @click="onSubmit">{{$t('search')}}</el-button>
  </el-form-item>
</el-form>
  <el-button  type="primary" @click="dialogAddVisible = true" >{{$t('add_user')}}</el-button>
 <el-table
      :data="itemList"
      style="width: 100%">
      <el-table-column
        prop="username"
        label="用户名"
        width="200">
      </el-table-column>
      <el-table-column
        prop="groupid"
        label="用户角色"
        :formatter="formatGroup"
        width="100">
      </el-table-column>
      <el-table-column
        prop="reg_time"
        label="注册时间"
        width="160">
      </el-table-column>
      <el-table-column
        prop="last_login_time"
        label="最后登录时间"
        width="160">
      </el-table-column>
      <el-table-column
        prop="item_domain"
        label="操作">
          <template slot-scope="scope">
            <el-button @click="click_password(scope.row)" type="text" size="small">修改密码</el-button>
            <el-button @click="delete_user(scope.row)" type="text" size="small">删除</el-button>
          </template>
      </el-table-column>
    </el-table>

  <div class="block">
    <span class="demonstration"></span>
      <el-pagination
        @current-change="handleCurrentChange"
        :page-size="count"
        layout="total, prev, pager, next"
        :total="total">
      </el-pagination>
  </div>

  <el-dialog :visible.sync="dialogVisible" :modal="false" width="300px">
    <el-form >
        <el-form-item label="" >
          <el-input type="password"  placeholder="新密码" v-model="Form.new_password"></el-input>
        </el-form-item>
    </el-form>
    <div slot="footer" class="dialog-footer">
      <el-button @click="dialogVisible = false">{{$t('cancel')}}</el-button>
      <el-button type="primary" @click="change_password" >{{$t('confirm')}}</el-button>
    </div>
  </el-dialog>

  <el-dialog :visible.sync="dialogAddVisible" :modal="false" width="300px">
    <el-form >
        <el-form-item label="" >
          <el-input type="text"  placeholder="登录名" v-model="addForm.username"></el-input>
        </el-form-item>
        <el-form-item label="" >
          <el-input type="password"  placeholder="密码" v-model="addForm.password"></el-input>
        </el-form-item>
    </el-form>
    <div slot="footer" class="dialog-footer">
      <el-button @click="dialogAddVisible = false">{{$t('cancel')}}</el-button>
      <el-button type="primary" @click="add_user" >{{$t('confirm')}}</el-button>
    </div>
  </el-dialog>

</div>

</template>

<style scoped>



</style>

<script>

export default {
  data() {
    return {
      itemList:[],
      username:'',
      page:1 ,
      count: 7 ,
      total:0,
      Form:{
        new_password:''
      },
      addForm:{
        username:'',
        password:'',
      },
      dialogVisible:false,
      dialogAddVisible:false,
      password_uid:''
    };
  },
  methods:{
    get_user_list(){
        var that = this ;
        var url = DocConfig.server+'/api/adminUser/getList';

        var params = new URLSearchParams();
        params.append('username', this.username);
        params.append('page', this.page);
        params.append('count', this.count);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              //that.$message.success("加载成功");
              var json = response.data.data ;
              that.itemList = json.users ;
              that.total = json.total;

            }else{
              that.$alert(response.data.error_message);
            }
            
          });
    },
    formatGroup(row, column){
      if (row ) {
        if (row.groupid == 1 ) {
          return "管理员";
        }
        else if (row.groupid == 2 ) {
          return "普通用户";
        }
        else{
          return "未知";
        }
      };
    },
    //跳转到项目
    jump_to_item(row){
      let url = '#/'+row.item_id;
      window.open(url);
    },
    handleCurrentChange(currentPage){
      this.page = currentPage ;
      this.get_user_list();
    },
    onSubmit(){
      this.page = 1 ;
      this.get_user_list();
    },
    delete_user(row){
        var that = this ;
        var url = DocConfig.server+'/api/adminUser/deleteUser';
          this.$confirm(that.$t('confirm_delete'), ' ', {
            confirmButtonText: that.$t('confirm'),
            cancelButtonText: that.$t('cancel'),
            type: 'warning'
          }).then(() => {
            var params = new URLSearchParams();
            params.append('uid', row.uid);
            that.axios.post(url, params)
              .then(function (response) {
                if (response.data.error_code === 0 ) {
                  that.$message.success("删除成功");
                  that.get_user_list();
                  that.username = '';
                }else{
                  that.$alert(response.data.error_message);
                }
                
              });
          })
    },
    click_password(row){
      this.dialogVisible = true ;
      this.password_uid = row.uid
    },
    change_password(){
      var that = this ;
      var url = DocConfig.server+'/api/adminUser/changePassword';

      var params = new URLSearchParams();
      params.append('uid',  that.password_uid);
      params.append('new_password', this.Form.new_password);

      that.axios.post(url, params)
        .then(function (response) {
          if (response.data.error_code === 0 ) {
              that.dialogVisible = false;
              that.Form.new_password = '';
              that.$message.success(that.$t("success"));

          }else{
            that.$alert(response.data.error_message);
          }
          
        })
        .catch(function (error) {
          console.log(error);
        });
    },
    add_user(){
      var that = this ;
      var url = DocConfig.server+'/api/adminUser/addUser';

      var params = new URLSearchParams();
      params.append('username',  that.addForm.username);
      params.append('password', this.addForm.password);

      that.axios.post(url, params)
        .then(function (response) {
          if (response.data.error_code === 0 ) {
              that.dialogAddVisible = false;
              that.addForm.password = '';
              that.addForm.username = '';
              that.$message.success(that.$t("success"));
              that.get_user_list();

          }else{
            that.$alert(response.data.error_message);
          }
          
        })
        .catch(function (error) {
          console.log(error);
        });
    }
  },
  mounted () {
    this.get_user_list();
    
  },
  beforeDestroy(){
    this.$message.closeAll();
    /*去掉添加的背景色*/
    document.body.removeAttribute("class","grey-bg");
  }
}
</script>