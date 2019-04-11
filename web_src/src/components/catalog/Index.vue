<template>
  <div class="hello">
    <Header> </Header>

    <el-container>
      <el-card class="center-card">

      <el-button  type="text" class="add-cat" @click="add_cat">{{$t('add_cat')}}</el-button>
      <el-button type="text" class="goback-btn" @click="goback">{{$t('goback')}}</el-button>
       <el-table align="left"
            :data="catalogs_table"
             height="400"
             :row-class-name="tableRowClassName"
            style="width: 100%">
            <el-table-column
              prop="cat_name"
              :label="$t('cat_name')"
              width="160">
            </el-table-column>
            <el-table-column
              prop="addtime"
              :label="$t('add_time')"
              width="160">
            </el-table-column>
            <el-table-column
              prop="s_number"
              :label="$t('s_number')">
            </el-table-column>
            <el-table-column
              prop=""
              width="110"
              :label="$t('operation')">
              <template slot-scope="scope">
                <el-button @click="edit(scope.row)" type="text" size="small">{{$t('edit')}}</el-button>
                <el-button @click="delete_cat(scope.row.cat_id)" type="text" size="small">{{$t('delete')}}</el-button>
              </template>
            </el-table-column>
          </el-table>


            </el-card>
      <el-dialog :visible.sync="dialogFormVisible"  width="300px">
        <el-form >
            <el-form-item :label="$t('cat_name')+' : '" >
              <el-input  :placeholder="$t('input_cat_name')" v-model="MyForm.cat_name"></el-input>
            </el-form-item>
            <el-form-item :label="$t('parent_cat_name')+' : '" >
              <el-select v-model="MyForm.parent_cat_id" :placeholder="$t('none')">
                  <el-option
                    v-for="item in parent_catalogs"
                    :key="item.cat_id"
                    :label="item.cat_name"
                    :value="item.cat_id">
                  </el-option>
                </el-select>
            </el-form-item>
            <el-form-item :label="$t('s_number')" >
              <el-input  :placeholder="$t('s_number_explain')" v-model="MyForm.s_number"></el-input>
            </el-form-item>
        </el-form>

        <div slot="footer" class="dialog-footer">
          <el-button @click="dialogFormVisible = false">{{$t('cancel')}}</el-button>
          <el-button type="primary" @click="MyFormSubmit" >{{$t('confirm')}}</el-button>
        </div>
      </el-dialog>
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
      MyForm:{
        cat_id:0,
        parent_cat_id:'',
        cat_name:'',
        s_number:''
      },
      catalogs:[],
      catalogs_level_2:[],
      dialogFormVisible:false,
    }

  },
  computed: {
    //表格形式展示目录数据
    catalogs_table:function(){
        var Info = this.catalogs.slice(0);
        var cat_array = [] ;
        for (var i = 0; i < Info.length; i++) {
          Info[i]['cat_name'] = Info[i]['cat_name'];
          cat_array.push(Info[i]);
          var sub = Info[i]['sub'] 
          if (sub.length > 0 ) {
            for (var j = 0; j < sub.length; j++) {
              cat_array.push( {
                "cat_id":sub[j]['cat_id'] ,
                "parent_cat_id":sub[j]['parent_cat_id'] ,
                "s_number":sub[j]['s_number'] ,
                "cat_name":' -- ' + sub[j]['cat_name']
              });
              var sub2 = sub[j]['sub'] ;
              for (var k = 0; k < sub2.length; k++) {
                cat_array.push( {
                  "cat_id":sub2[k]['cat_id'] ,
                  "s_number":sub2[k]['s_number'] ,
                  "parent_cat_id":sub2[k]['parent_cat_id'] ,
                  "cat_name":' ----- ' + sub2[k]['cat_name']
                });
              };

            };
          };
          
        };
        return cat_array;
    },
    //新建/编辑目录时供用户选择的上级目录列表
    parent_catalogs:function(){
        var Info = this.catalogs.slice(0);
        var cat_array = [] ;
        for (var i = 0; i < Info.length; i++) {
          cat_array.push(Info[i]);
          var sub = Info[i]['sub'] 
          if (sub.length > 0 ) {
            for (var j = 0; j < sub.length; j++) {
              cat_array.push( {
                "cat_id":sub[j]['cat_id'] ,
                "cat_name":Info[i]['cat_name']+' / ' + sub[j]['cat_name']
              });
            };
          };
        };
        var no_cat = {"cat_id":0 ,"cat_name":this.$t("none")} ;
        cat_array.push(no_cat);
        return cat_array;

    }
  },
  methods: {

      tableRowClassName({row, rowIndex}) {
        if (row['level'] == '2') {
          return 'success-row';
        }
        return '';
      },

      get_catalog(){
        var that = this ;
        var url = DocConfig.server+'/api/catalog/catListGroup';
        var params = new URLSearchParams();
        params.append('item_id',  that.$route.params.item_id);
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              var Info = response.data.data ;
              that.catalogs =  Info;
            }else{
              that.$alert(response.data.error_message);
            }
            
          })
          .catch(function (error) {
            console.log(error);
          });
      },
      MyFormSubmit() {
          var that = this ;
          var url = DocConfig.server+'/api/catalog/save';

          var params = new URLSearchParams();
          params.append('item_id',  that.$route.params.item_id);
          params.append('cat_id', this.MyForm.cat_id);
          params.append('parent_cat_id', this.MyForm.parent_cat_id);
          params.append('cat_name', this.MyForm.cat_name);
          params.append('s_number', this.MyForm.s_number);

          that.axios.post(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                that.dialogFormVisible = false;
                that.get_catalog() ;
                that.MyForm = [];
              }else{
                that.$alert(response.data.error_message);
              }
              
            })
            .catch(function (error) {
              console.log(error);
            });
      },
      edit(row){
         var temp={};  
         temp = JSON.parse(JSON.stringify(row));  
        if (temp.cat_name) {
          temp.cat_name = temp.cat_name.replace(' -- ','');
          temp.cat_name = temp.cat_name.replace(' ----- ','');
        };
        if (temp.parent_cat_id == '0') {
          temp.parent_cat_id = '';
        };

        this.MyForm = temp;

        this.dialogFormVisible = true;
      },

      delete_cat(cat_id){
          var that = this ;
          var url = DocConfig.server+'/api/catalog/delete';

          this.$confirm(that.$t('confirm_cat_delete'), ' ', {
            confirmButtonText: that.$t('confirm'),
            cancelButtonText: that.$t('cancel'),
            type: 'warning'
          }).then(() => {
            var params = new URLSearchParams();
            params.append('item_id',  that.$route.params.item_id);
            params.append('cat_id', cat_id);

            that.axios.post(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                that.get_catalog() ;
              }else{
                that.$alert(response.data.error_message);
              }
              
            }); 
          })

      },
      add_cat(){
        this.MyForm = [] ;
        this.dialogFormVisible = true;

      },
      goback(){
        var url = '/'+this.$route.params.item_id;
        this.$router.push({path:url})
      }
  },

  mounted(){
    this.get_catalog();
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>

.hello{
  text-align: left;
}

.add-cat{
  margin-left: 10px;
}

.center-card{
  text-align: left;
  width: 600px;
  height: 500px;
}

.goback-btn{
  z-index: 999;
  margin-left: 400px;
}
</style>

<!-- 全局css -->
<style >
.el-table .success-row {
  background: #f0f9eb;
}

</style>
