<template>
  <div class="hello">
    <Header> </Header>

    <el-container>
      <el-card class="center-card">

        <el-row>
          <el-button  type="text" class="add-cat" @click="add_cat">{{$t('add_cat')}}</el-button>
          <el-button type="text" class="goback-btn" @click="goback">{{$t('goback')}}</el-button>
        </el-row>
        <p class="tips" v-if="treeData.length > 1">{{$t('cat_tips')}}</p>
        <el-tree
          class="tree-node"
          :data="treeData"
          node-key="id"
          default-expand-all
          @node-drag-end="handleDragEnd"
          draggable
          >

          <span class="custom-tree-node" slot-scope="{ node, data }">
            <span>{{ node.label }}</span>
            <span class="right-bar">

              <el-button
                type="text"
                size="mini"
                 class="el-icon-edit"
                @click.stop="edit(node, data)">
              </el-button>
              <el-button
                type="text"
                size="mini"
                 class="el-icon-document"
                @click.stop="showSortPage(node, data)">
              </el-button>
              <el-button
                type="text"
                size="mini"
                class="el-icon-delete"
                @click.stop="delete_cat(node, data)">
                
              </el-button>
            </span>
          </span>
        </el-tree>


      </el-card>
      <el-dialog :visible.sync="dialogFormVisible"  width="300px" :close-on-click-modal="false">
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
        </el-form>

        <div slot="footer" class="dialog-footer">
          <el-button @click="dialogFormVisible = false">{{$t('cancel')}}</el-button>
          <el-button type="primary" @click="MyFormSubmit" >{{$t('confirm')}}</el-button>
        </div>
      </el-dialog>
    </el-container>

    <SortPage :callback="insertValue" :belong_to_catalogs="belong_to_catalogs" :item_id="item_id"  :cat_id="curl_cat_id" ref="SortPage"></SortPage>

    <Footer> </Footer>
  </div>
</template>

<script>

import SortPage from '@/components/page/edit/SortPage'
export default {
  name: 'Login',
  components : {
    SortPage
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
      dialogFormVisible:false,
      treeData: [],
      defaultProps: {
        children: 'children',
        label: 'label'
      },
      item_id:'',
      curl_cat_id:''
    }

  },
  computed: {
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

    },
    //新建/编辑页面时供用户选择的归属目录列表
    belong_to_catalogs:function(){
        if (!this.catalogs || this.catalogs.length <=0 ) {
          return [];
        };
        var Info = this.catalogs.slice(0);
        var cat_array = [] ;
        for (var i = 0; i < Info.length; i++) {
          cat_array.push(Info[i]);
          var sub = Info[i]['sub'] ;
          if (sub.length > 0 ) {
            for (var j = 0; j < sub.length; j++) {
              cat_array.push( {
                "cat_id":sub[j]['cat_id'] ,
                "cat_name":Info[i]['cat_name']+' / ' + sub[j]['cat_name']
              });

              var sub_sub = sub[j]['sub'] ;
              if (sub_sub.length > 0 ) {
                for (var k = 0; k < sub_sub.length; k++) {
                  cat_array.push( {
                    "cat_id":sub_sub[k]['cat_id'] ,
                    "cat_name":Info[i]['cat_name']+' / ' + sub[j]['cat_name']+' / ' + sub_sub[k]['cat_name']
                  });
                };
              };

            };
          };
        };
        var no_cat = {"cat_id":'' ,"cat_name":this.$t("none")} ;
        cat_array.unshift(no_cat);
        return cat_array;

    }

  }, 
  methods: {
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
              that.treeData = [];
              for (var i = 0; i < Info.length; i++) {
                let node = {'children':[]};
                node['id'] = Info[i]['cat_id'];
                node['label'] = Info[i]['cat_name'];
                if (Info[i]['sub'].length > 0 ) {
                  for (var j = 0; j < Info[i]['sub'].length; j++) {
                      let node2 = {'children':[]};
                      node2['id'] = Info[i]['sub'][j]['cat_id'];
                      node2['label'] = Info[i]['sub'][j]['cat_name'];
                      if (Info[i]['sub'][j]['sub'].length > 0 ) {
                        for (var k = 0; k < Info[i]['sub'][j]['sub'].length; k++) {
                            let node3 = {};
                            node3['id'] = Info[i]['sub'][j]['sub'][k]['cat_id'];
                            node3['label'] = Info[i]['sub'][j]['sub'][k]['cat_name'];
                            node2['children'].push(node3);
                        };
                      };
                    node['children'].push(node2);
                  };
                  
                };
                that.treeData.push(node);
              };
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
      edit(node, data){
        this.MyForm = {
          cat_id:data.id,
          parent_cat_id: node.parent.data.id,
          cat_name:data.label,
        };

        this.dialogFormVisible = true;
      },

      delete_cat(node, data){
          var that = this ;
          var cat_id = data.id ;
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
      },
      handleDragEnd(node1, node2 , position , evt){
        var that = this ;
        if (!this.checkCat(this.treeData)) {
          this.$alert(this.$t("cat_limite_tips"));
          this.get_catalog();
          return ;
        };
        let treeData2 = this.dimensionReduction(this.treeData) ;
        var url = DocConfig.server+'/api/catalog/batUpdate';
        var params = new URLSearchParams();
        params.append('item_id',  that.$route.params.item_id);
        params.append('cats',  JSON.stringify(treeData2));
        that.axios.post(url, params)
          .then( (response)=> {
              if (response.data.error_code === 0 ) {

              }else{
                that.$alert(response.data.error_message);
              }
          })

      },
      //检查合法性，不能超过三个层级目录
      checkCat(treeData){
        for (var i = 0; i < treeData.length; i++) {
          if (!treeData[i].hasOwnProperty('children')) {
            continue ;
          };
          for (var j = 0; j < treeData[i]['children'].length; j++) {
            if (!treeData[i]['children'][j].hasOwnProperty('children')) {
              continue ;
            };
            for (var k = 0; k < treeData[i]['children'][j]['children'].length; k++) {
              if (! treeData[i]['children'][j]['children'][k].hasOwnProperty('children')) {
                continue ;
              };
              if (treeData[i]['children'][j]['children'][k]['children'].length > 0 ) {
                return false ;
              };
       
            };
            
          };
          
        };

        return true ;
      },
      //将目录数组降维
      dimensionReduction(treeData){
        let treeData2 = []; 
        for (var i = 0; i < treeData.length; i++) {
          treeData2.push({
            'cat_id' : treeData[i]['id'] ,
            'cat_name' : treeData[i]['label'] ,
            'parent_cat_id' : 0 ,
            'level' : 2 ,
            's_number' : (i+1) ,
          });
          if (!treeData[i].hasOwnProperty('children')) {
            continue ;
          };
          for (var j = 0; j < treeData[i]['children'].length; j++) {

            treeData2.push({
              'cat_id' : treeData[i]['children'][j]['id'] ,
              'cat_name' : treeData[i]['children'][j]['label'] ,
              'parent_cat_id' : treeData[i]['id'] ,
              'level' : 3 ,
              's_number' : (j+1) ,
            });

            if (!treeData[i]['children'][j].hasOwnProperty('children')) {
              continue ;
            };
            for (var k = 0; k < treeData[i]['children'][j]['children'].length; k++) {
              treeData2.push({
                'cat_id' : treeData[i]['children'][j]['children'][k]['id'] ,
                'cat_name' : treeData[i]['children'][j]['children'][k]['label'] ,
                'parent_cat_id' : treeData[i]['children'][j]['id'] ,
                'level' : 4 ,
                's_number' : (k+1) ,
              });
       
            };
            
          };
          
        };
        return treeData2 ;
      },
      //展示页面排序
      showSortPage(node, data){
          this.curl_cat_id = data.id;
          let childRef = this.$refs.SortPage ;//获取子组件
          childRef.show() ; 
      },

  },

  mounted(){
    this.get_catalog();
    this.item_id = this.$route.params.item_id ;
  },
  
  beforeDestroy(){

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
  min-height: 500px;
  max-height: 90%;
  overflow-y:auto; 
}

.goback-btn{
  z-index: 999;
  margin-left: 400px;
}

.cat-box{
  background-color:rgb(250, 250, 250);
  width: 100%;
  height: 40px;
  margin-bottom: 10px;
  border: 1px solid #d9d9d9;
  border-radius: 2px;
}
.cat-name{
  line-height: 40px;
  margin-left: 20px;
  color: #262626;
}
.tree-node{
  margin-top: 20px;
}
.custom-tree-node{
  width: 100%;
}
.right-bar{
  float: right;
  margin-right: 20px;
}

.tips{
  margin-left: 10px;
  color: #9ea1a6;
  font-size: 11px;

}

</style>
