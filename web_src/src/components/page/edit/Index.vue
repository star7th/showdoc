<template>
  <div class="hello" @keydown.ctrl.83.prevent="save" @keydown.meta.83.prevent="save">
    <Header> </Header>

    <el-container class="container-narrow">

      <el-row class="masthead">
        <el-form :inline="true"   class="demo-form-inline" size="small">
          <el-form-item :label="$t('title')+' : '">
            <el-input  placeholder="" v-model="title"></el-input>
          </el-form-item>
          <el-form-item :label="$t('level_2_directory')+' : '" >
            <el-select  :placeholder="$t('optional')" class="cat" v-model="cat_id2" @change="get_cat3">
              <el-option v-if="cat2" v-for="cat in cat2 " :key="cat.cat_name" :label="cat.cat_name" :value="cat.cat_id"></el-option>
            </el-select>
          </el-form-item>
          <el-form-item :label="$t('level_3_directory')+' : '" >
            <el-select  :placeholder="$t('optional')" class="cat" v-model="cat_id3">
              <el-option v-if="cat3" v-for="cat in cat3 " :label="cat.cat_name" :key="cat.cat_name" :value="cat.cat_id"></el-option>
            </el-select>
          </el-form-item>
          <el-form-item :label="$t('s_number')+' : '">
            <el-input  :placeholder="$t('optional')" class="num" v-model="s_number"></el-input>
          </el-form-item>
          <el-form-item label="" >
            <el-button type="text" @click="ShowHistoryVersion">{{$t('history_version')}}</el-button>
          </el-form-item>

          <el-form-item class="pull-right">
              <el-dropdown  @command="dropdown_callback" split-button type="primary" size="medium" trigger="click" @click="save">
                {{$t('save')}}
                <el-dropdown-menu slot="dropdown">
                  <el-dropdown-item :command="save_to_template">{{$t('save_to_templ')}}</el-dropdown-item>
                  <!-- <el-dropdown-item>保存前添加注释</el-dropdown-item> -->
                </el-dropdown-menu>
              </el-dropdown>
            <el-button type="" size="medium" @click="goback">{{$t('goback')}}</el-button>
          </el-form-item>
        </el-form>

        <el-row class="fun-btn-group">
          <el-button type="" size="medium" @click="insert_api_template">{{$t('insert_apidoc_template')}}</el-button>
          <el-button type="" size="medium" @click="insert_database_template">{{$t('insert_database_doc_template')}}</el-button>
          <el-button type="" size="medium" @click.native="ShowTemplateList">{{$t('more_templ')}}</el-button>
            <el-dropdown split-button type="" style="margin-left:100px;" size="medium" trigger="hover" >
              {{$t('json_tools')}}
              <el-dropdown-menu slot="dropdown">
                <el-dropdown-item @click.native="ShowJsonToTable">{{$t('json_to_table')}}</el-dropdown-item>
                <el-dropdown-item @click.native="ShowJsonBeautify">{{$t('beautify_json')}}</el-dropdown-item>
              </el-dropdown-menu>
            </el-dropdown>
          <el-button type="" size="medium" @click="ShowRunApi">{{$t('http_test_api')}}</el-button>

          </el-row>

      <Editormd v-bind:content="content" v-if="content" ref="Editormd"  type="editor" ></Editormd>

        
      </el-row>

        <!-- 更多模板 -->
        <TemplateList :callback="insertValue" ref="TemplateList"></TemplateList>

        <!-- 历史版本 -->
        <HistoryVersion :callback="insertValue" ref="HistoryVersion"></HistoryVersion>

        <!-- Json转表格 组件 -->
        <JsonToTable   :callback="insertValue" ref="JsonToTable" ></JsonToTable>

        <!-- Json格式化 -->
        <JsonBeautify :callback="insertValue" ref="JsonBeautify"></JsonBeautify>

      </el-container>
    <Footer> </Footer>
    <div class=""></div>
<!-- 模板存放的地方 -->
<div id="api-doc-templ"  ref="api_doc_templ" style="display:none">
    
**简要描述：** 

- 用户注册接口

**请求URL：** 
- ` http://xx.com/api/user/register `
  
**请求方式：**
- POST 

**参数：** 

|参数名|必选|类型|说明|
|:----    |:---|:----- |-----   |
|username |是  |string |用户名   |
|password |是  |string | 密码    |
|name     |否  |string | 昵称    |

 **返回示例**

``` 
  {
    "error_code": 0,
    "data": {
      "uid": "1",
      "username": "12154545",
      "name": "吴系挂",
      "groupid": 2 ,
      "reg_time": "1436864169",
      "last_login_time": "0",
    }
  }
```

 **返回参数说明** 

|参数名|类型|说明|
|:-----  |:-----|-----                           |
|groupid |int   |用户组id，1：超级管理员；2：普通用户  |

 **备注** 

- 更多返回错误代码请看首页的错误代码描述


</div>
<div id="database-doc-templ" ref="database_doc_templ" style="display:none">
    
-  用户表，储存用户信息

|字段|类型|空|默认|注释|
|:----    |:-------    |:--- |-- -|------      |
|uid    |int(10)     |否 |  |             |
|username |varchar(20) |否 |    |   用户名  |
|password |varchar(50) |否   |    |   密码    |
|name     |varchar(15) |是   |    |    昵称     |
|reg_time |int(11)     |否   | 0  |   注册时间  |

- 备注：无


</div>

  </div>
</template>

<style scoped>


  .container-narrow{
    margin: 0 auto;
    max-width: 90%;
  }

  .masthead{
    width: 100%;
    margin-top: 5px;
  }

  .cat{
    width: 130px;
  }

  .num{
    width: 60px;

  }
  .fun-btn-group{
    margin-top: 15px;
    margin-bottom: 15px;
  }

</style>

<script>
import Editormd from '@/components/common/Editormd'
import JsonToTable from '@/components/common/JsonToTable'
import JsonBeautify from '@/components/common/JsonBeautify'
import TemplateList from '@/components/page/edit/TemplateList'
import HistoryVersion from '@/components/page/edit/HistoryVersion'

export default {
  data () {
    return {
      currentDate: new Date(),
      itemList:{},
      content:"",
      title:"",
      item_id:0,
      cat2:[],
      cat_id2:'',
      cat3:[],
      cat_id3:'',
      s_number:'',
      page_id:'',
      copy_page_id:'',

    };
  },
  components:{
    Editormd,
    JsonToTable,
    JsonBeautify,
    TemplateList,
    HistoryVersion
  },
  methods:{
    //获取页面内容
    get_page_content(page_id){
        if (!page_id) {
          var page_id = this.page_id ;
        };
        var that = this ;
        var url = DocConfig.server+'/api/page/info';
        var params = new URLSearchParams();
        params.append('page_id', page_id );
        that.axios.post(url, params)
          .then(function (response) {
            if (response.data.error_code === 0 ) {
              //that.$message.success("加载成功");
              that.content = response.data.data.page_content ;
              setTimeout(function(){
                that.insertValue(that.content ,1) ;
              },500);
              that.title = response.data.data.page_title ;
              that.item_id = response.data.data.item_id ;
              that.s_number = response.data.data.s_number ;
            }else{
              that.$alert(response.data.error_message);
            }
            
          })
          .catch(function (error) {
            console.log(error);
          });
    },

    //获取二级目录
    get_cat2(item_id){
      var that = this ;
      var url = DocConfig.server+'/api/catalog/secondCatList';
      var params = new URLSearchParams();
      params.append('item_id',  item_id);
      that.axios.post(url, params)
        .then(function (response) {
          if (response.data.error_code === 0 ) {
            //that.$message.success("加载成功");
            that.cat2 = response.data.data ;
            var no_cat = {"cat_id":'' ,"cat_name":" "} ;
            that.cat2.unshift(no_cat);
            that.get_default_cat();
          }else{
            that.$alert(response.data.error_message);
          }
          
        });
    },
    //获取三级目录
    get_cat3(cat_id,callback){
      var that = this ;
      var url = DocConfig.server+'/api/catalog/childCatList';
      var params = new URLSearchParams();
      params.append('cat_id',  cat_id);
      that.axios.post(url, params)
        .then(function (response) {
          if (response.data.error_code === 0 ) {
            //that.$message.success("加载成功");
            that.cat_id3 = '';
            that.cat3 = response.data.data ;
            if(callback){
              callback();
            }

          }else{
            that.$alert(response.data.error_message);
          }
          
        });
    },
    //获取默认该选中的目录
    get_default_cat(){
      var that = this ;
      var url = DocConfig.server+'/api/catalog/getDefaultCat';
      var params = new URLSearchParams();
      params.append('page_id',  this.page_id);
      params.append('item_id',  that.$route.params.item_id);
      params.append('copy_page_id',  this.copy_page_id);

      that.axios.post(url, params)
        .then(function (response) {
          if (response.data.error_code === 0 ) {
            //that.$message.success("加载成功");
            var json = response.data.data ;
            that.cat_id2 = json.default_cat_id2 ;
            that.get_cat3(json.default_cat_id2,function(){
              that.cat_id3 = json.default_cat_id3 ;
            }) ;

          }else{
            that.$alert(response.data.error_message);
          }
          
        });
    },
    //插入数据到编辑器中。插入到光标处。如果参数is_cover为真，则清空后再插入(即覆盖)。
    insertValue(value,is_cover){
      if (value) {
        let childRef = this.$refs.Editormd ;//获取子组件
        if (is_cover) {
          // 清空
          childRef.clear();
        };
        childRef.insertValue(value) ; //调用子组件的方法
      };

    },

    //插入api模板
    insert_api_template(){
      this.insertValue(this.$refs.api_doc_templ.innerHTML ) ;
    },

    //插入数据字典模板
    insert_database_template(){
      this.insertValue(this.$refs.database_doc_templ.innerHTML ) ;
    },

    //json转参数表格
    ShowJsonToTable(){
        let childRef = this.$refs.JsonToTable ;//获取子组件
        childRef.dialogFormVisible = true ; 
    },
    //json格式化
    ShowJsonBeautify(){
        let childRef = this.$refs.JsonBeautify ;//获取子组件
        childRef.dialogFormVisible = true ; 
    },

    ShowRunApi(){
      window.open('http://runapi.showdoc.cc/');
    },
    //更多模板、模板列表
    ShowTemplateList(){
        let childRef = this.$refs.TemplateList ;//获取子组件
        childRef.show() ; 
    },

    //展示历史版本
    ShowHistoryVersion(){
        let childRef = this.$refs.HistoryVersion ;//获取子组件
        childRef.show() ; 
    },

    save(){
      var that = this ;
      var loading = that.$loading();
      let childRef = this.$refs.Editormd ;
      var content = childRef.getMarkdown() ;
      var cat_id = 0 ;
      if (that.cat_id2 > 0 ) {
        cat_id = that.cat_id2 ;
      };     
      if (that.cat_id3 > 0 ) {
        cat_id = that.cat_id3 ;
      };
      var item_id = that.$route.params.item_id ;
      var page_id = that.$route.params.page_id ;
      var url = DocConfig.server+'/api/page/save';
      var params = new URLSearchParams();
      params.append('page_id',  page_id);
      params.append('item_id',  item_id);
      params.append('s_number',  that.s_number);
      params.append('page_title',  that.title);
      params.append('page_content',  encodeURIComponent(content));
      params.append('is_urlencode',  1);
      params.append('cat_id',  cat_id);
      that.axios.post(url, params)
        .then(function (response) {
          loading.close();
          if (response.data.error_code === 0 ) {
            that.$message({
              showClose: true,
              message: that.$t("save_success"),
              type: 'success'
            });
            localStorage.removeItem("page_content");
            if (page_id <= 0 ) {
              that.$router.push({path:'/page/edit/'+item_id+'/'+response.data.data.page_id}) ;
            };
          }else{
            that.$alert(response.data.error_message);
          }
          
        });
        //设置一个最长关闭时间
        setTimeout(() => {
          loading.close();
        }, 20000);
    },
    goback(){
      localStorage.removeItem("page_content");
      var url = '/'+this.$route.params.item_id;
      this.$router.push({path:url,query:{page_id:this.$route.params.page_id}}) ; 
    },
    dropdown_callback(data){
      if (data) {
        data();
      };
    },
    //另存为模板
    save_to_template(){
      var that = this ;
      let childRef = this.$refs.Editormd ;
      var content = childRef.getMarkdown() ;
       this.$prompt(that.$t("save_templ_title"), ' ', {
       }).then(function(data){
          var url = DocConfig.server+'/api/template/save';
          var params = new URLSearchParams();
          params.append('template_title',  data.value);
          params.append('template_content',  content);
          that.axios.post(url, params)
            .then(function (response) {
              if (response.data.error_code === 0 ) {
                  that.$alert(that.$t("save_templ_text"));
              }else{
                that.$alert(response.data.error_message);
              }
              
            });
       });
    },

    /** 粘贴上传图片 **/
    upload_paste_img(e){
      var that = this;
      var url = DocConfig.server+'/api/page/uploadImg';
      var clipboard = e.clipboardData;
      for (var i = 0, len = clipboard.items.length; i < len; i++) {
        if (clipboard.items[i].kind == 'file' || clipboard.items[i].type.indexOf('image') > -1) {
          var imageFile = clipboard.items[i].getAsFile();
          var form = new FormData;
          form.append('t', 'ajax-uploadpic');
          form.append('editormd-image-file', imageFile);
          var loading = '';
          var callback = function(type, data) {
            type = type || 'before';
            switch (type) {
              // 开始上传
              case 'before':
                loading = that.$loading();
                break;
                // 服务器返回错误
              case 'error':
                loading.close();
                that.$alert('图片上传失败');
                break;
                // 上传成功
              case 'success':
                loading.close();
                if (data.success == 1) {
                  var value = '![](' + data.url + ')';
                  that.insertValue(value);
                } else {
                  that.$alert(data.message);
                }

                break;
            }
          };
          $.ajax({
            url: url,
            type: "POST",
            dataType: "json",
            data: form,
            processData: false,
            contentType: false,
            beforeSend: function() {
              callback('before');
            },
            error: function() {
              callback('error');
            },
            success: function(data) {
              callback('success', data);
            }
          })
          e.preventDefault();
        }
      }
    }
  },

  mounted () {
    var that = this ;
    this.page_id = this.$route.params.page_id ;
    this.copy_page_id = this.$route.query.copy_page_id ? this.$route.query.copy_page_id : '' ;
    
    if (this.copy_page_id > 0 ) {
      this.get_page_content(this.copy_page_id);
    }
    else if (this.page_id > 0 ) {
      this.get_page_content(this.page_id);
    }else{
      this.item_id = this.$route.params.item_id ;
      this.content = this.$t("welcome_use_showdoc") ;
    }
    this.get_cat2(this.$route.params.item_id);

    /** 监听粘贴上传图片 **/
    document.addEventListener('paste', this.upload_paste_img);
    
    
  },

  beforeDestroy(){
    
    //解除对粘贴上传图片的监听
    document.removeEventListener('paste', this.upload_paste_img);
  }
}
</script>
