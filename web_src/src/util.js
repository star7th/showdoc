// 全局函数/变量
export default {
  install(Vue, options) {
    Vue.prototype.getData = function () {
      console.log('我是插件中的方法')
    }

    // Vue.prototype.DocConfig = {
    // "server":'http://127.0.0.1/showdoc.cc/server/index.php?s=',
    // "server":'../server/index.php?s=',
    // }
    Vue.prototype.request = function () {

    }

    Vue.prototype.getRootPath = function () {
      return window.location.protocol + '//' + window.location.host + window.location.pathname
    }

    /* 判断是否是移动设备 */
    Vue.prototype.isMobile = function () {
      return !!navigator.userAgent.match(/iPhone|iPad|iPod|Android|android|BlackBerry|IEMobile/i)
    }

    Vue.prototype.get_user_info = function (callback) {
      var that = this
      var url = DocConfig.server + '/api/user/info'
      var params = new URLSearchParams()
      params.append('redirect_login', false)
      that.axios.post(url, params)
        .then(function (response) {
          if (callback) { callback(response) };
        })
    }

    Vue.prototype.get_notice = function (callback) {
      var that = this
      var url = DocConfig.server + '/api/notice/getList'
      var params = new URLSearchParams()
      params.append('notice_type', 'unread')
      params.append('count', '1')
      that.axios.post(url, params)
        .then(function (response) {
          if (callback) { callback(response) };
        })
    }

    Vue.prototype.set_bg_grey = function () {
      /* 给body添加类，设置背景色 */
      document.getElementsByTagName('body')[0].className = 'grey-bg'
    }

    Vue.prototype.unset_bg_grey = function () {
      /* 去掉添加的背景色 */
      document.body.removeAttribute('class', 'grey-bg')
    }

    // json格式化与压缩
    // compress=false的时候表示美化json，compress=true的时候表示将美化过的json压缩还原
    Vue.prototype.formatJson = function (txt, compress = false) {
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
      const indentChar = ' '
      if (/^\s*$/.test(txt)) {
        // alert('数据为空,无法格式化! ');
        return txt
      }
      try {
        var data = eval(`(${txt})`)
      } catch (e) {
        // alert(`数据源语法错误,格式化失败! 错误信息: ${e.description}`, 'err');
        return txt
      }
      const draw = []
      const last = false
      const This = this
      const line = compress ? '' : '\n'
      let nodeCount = 0
      let maxDepth = 0

      const notify = function (name, value, isLast, indent, formObj) {
        nodeCount++ /* 节点计数 */
        for (var i = 0, tab = ''; i < indent; i++) tab += indentChar /* 缩进HTML */
        tab = compress ? '' : tab /* 压缩模式忽略缩进 */
        maxDepth = ++indent /* 缩进递增并记录 */
        if (value && value.constructor == Array) {
          /* 处理数组 */
          draw.push(`${tab + (formObj ? `"${name}":` : '')}[${line}`) /* 缩进'[' 然后换行 */
          for (var i = 0; i < value.length; i++) { notify(i, value[i], i == value.length - 1, indent, false) }
          draw.push(`${tab}]${isLast ? line : `,${line}`}`) /* 缩进']'换行,若非尾元素则添加逗号 */
        } else if (value && typeof value === 'object') {
          /* 处理对象 */
          draw.push(`${tab + (formObj ? `"${name}":` : '')}{${line}`) /* 缩进'{' 然后换行 */
          let len = 0
          var i = 0
          for (var key in value) len++
          for (var key in value) notify(key, value[key], ++i == len, indent, true)
          draw.push(`${tab}}${isLast ? line : `,${line}`}`) /* 缩进'}'换行,若非尾元素则添加逗号 */
        } else {
          if (typeof value === 'string') value = `"${value}"`
          draw.push(tab + (formObj ? `"${name}":` : '') + value + (isLast ? '' : ',') + line)
        }
      }
      const isLast = true
      const indent = 0
      notify('', data, isLast, indent, false)
      return draw.join('')
    }
  }
}
