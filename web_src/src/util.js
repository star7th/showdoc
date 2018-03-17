//全局函数/变量
export default{
  install(Vue,options)
  {
    Vue.prototype.getData = function () {
      console.log('我是插件中的方法');
    }

    //Vue.prototype.DocConfig = {
     // "server":'http://127.0.0.1/showdoc.cc/server/index.php?s=',
      //"server":'../server/index.php?s=',
    //}
    Vue.prototype.request = function(){
    	
    }

    Vue.prototype.getRootPath = function(){
        return window.location.protocol +'//' +window.location.host + window.location.pathname
    }

    /*判断是否是移动设备*/
    Vue.prototype.isMobile = function (){
      return navigator.userAgent.match(/iPhone|iPad|iPod|Android|android|BlackBerry|IEMobile/i) ? true : false; 
    }

    Vue.prototype.get_user_info = function(callback){
        var that = this ;
        var url = DocConfig.server+'/api/user/info';
        var params = new URLSearchParams();
        params.append('redirect_login', false);
        that.axios.post(url, params)
          .then(function (response) {
            if (callback) {callback(response);};
          });
    }

    Vue.prototype.get_notice = function(callback){
        var that = this ;
        var url = DocConfig.server+'/api/notice/getList';
        var params = new URLSearchParams();
        params.append('notice_type', 'unread');
        params.append('count', '1');
        that.axios.post(url, params)
          .then(function (response) {
            if (callback) {callback(response);};
          });
    }

  }
}