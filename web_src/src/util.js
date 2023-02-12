// 全局函数/变量
export default {
  install(Vue, options) {
    Vue.prototype.getData = function() {
      console.log('我是插件中的方法')
    }

    // Vue.prototype.DocConfig = {
    // "server":'http://127.0.0.1/showdoc.cc/server/index.php?s=',
    // "server":'../server/index.php?s=',
    // }
    Vue.prototype.request = function() {}

    Vue.prototype.getRootPath = function() {
      return (
        window.location.protocol +
        '//' +
        window.location.host +
        window.location.pathname
      )
    }

    /* 判断是否是移动设备 */
    Vue.prototype.isMobile = function() {
      return !!navigator.userAgent.match(
        /iPhone|iPad|iPod|Android|android|BlackBerry|IEMobile/i
      )
    }

    // json格式化与压缩
    // compress=false的时候表示美化json，compress=true的时候表示将美化过的json压缩还原
    Vue.prototype.formatJson = function(txt, compress = false) {
      if (compress === false) {
        try {
          if (typeof txt === 'string') {
            txt = JSON.parse(txt)
          }
          return JSON.stringify(txt, null, 2)
        } catch (e) {
          // 非json数据直接显示
          return txt
        }
      }
      // 将美化过的json压缩还原
      try {
        const obj = JSON.parse(txt)
        return JSON.stringify(obj)
      } catch (e) {
        // 非json数据直接显示
        return txt
      }
    }

    /* 刷新 */
    Vue.prototype.reload = function() {
      return window.location.reload()
    }
    Vue.prototype.toOutLink = function(url) {
      return window.open(url)
    }
  }
}
