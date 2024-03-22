<template>
  <div></div>
</template>
<script>
export default {
  data() {
    return {}
  },
  mounted() {
    var that = this
    // 循环检测jq有没有加载成功
    var check_jQuery_is_load = setInterval(function() {
      try {
        if ($ != undefined) {
          clearInterval(check_jQuery_is_load)
          that.tocMainScript()
          if ($('.markdown-toc').length && $('#toc-pos').length) {
            // 元素存在的处理逻辑
            const elementA = $('.markdown-toc')
            $('#toc-pos').append(elementA)
            // elementA.remove()
            $('#toc-pos').css('width', '160px')
          } else {
            $('#toc-pos').css('width', '0px')
          }
        }
      } catch (e) {}
    }, 500)
  },
  methods: {
    tocMainScript() {
      // 监听点击事件并滑动到相应位置
      $(document).on('click', '.markdown-toc-list a[href]', function(event) {
        event.preventDefault()
        event.stopPropagation()
        var name = $(this)
          .attr('href')
          .substring(1)
        var top_at_window = $('[name="' + name + '"]').offset().top
        var offset = -130
        $('html, body').animate({ scrollTop: top_at_window + offset }, 300)
      })
      // 监听展开事件
      $(document).on('click', '.markdown-toc', function(event) {
        if (!$(event.target).is('a')) {
          $('.markdown-toc').toggleClass('open-list')
        }
      })
      // 监听鼠标滚动事件给与颜色加亮
      $(window).scroll(function(event) {
        var activeName = ''
        $('.markdown-toc-list a[href]').each(function(index, el) {
          var name = $(el)
            .attr('href')
            .substring(1)
          if (
            $('[name="' + name + '"]').offset().top - $(window).scrollTop() <
            150
          ) {
            activeName = name
          } else {
            return false
          }
        })
        $('.markdown-toc-list a').removeClass('current')
        $('.markdown-toc-list a[href="#' + activeName + '"]').addClass(
          'current'
        )
      })

      // 不是移动设备并且不是其他小屏设备，则自动展开目录
      if (!this.isMobile() && window.screen.width > 1000) {
        this.$nextTick(() => {
          setTimeout(function() {
            $('.markdown-toc').click()
          }, 200)
        })
      }
    }
  },
  destroyed() {
    // 把那些监听了的事件去掉
    $(document).off('click', '.markdown-toc-list a[href]')
    $(document).off('click', '.markdown-toc')
    $('.markdown-toc').remove()
  }
}
</script>
<!-- 注意，这里是全局css -->
<style>
.page_content_main .markdown-toc {
  position: fixed;
  top: 155px;
  margin-left: 820px;
  min-width: 32px;
  min-height: 32px;
  cursor: pointer;
  z-index: 1;
  font-size: 13px;
}
#toc-pos .markdown-toc {
  position: fixed;
  top: 155px;
  min-width: 32px;
  min-height: 32px;
  cursor: pointer;
  z-index: 1;
  font-size: 13px;
}
.page_content_main .markdown-toc:before,
#toc-pos .markdown-toc:before {
  content: '\e63f';
  font-family: element-icons !important;
  color: #343a40;
  position: absolute;
  bottom: 0;
  right: 0;
  width: 32px;
  height: 32px;
  line-height: 32px;
  text-align: center;
  background: #fff;
  border: 1px solid #dcdfe6;
  border-radius: 5px;
  cursor: pointer;
  box-shadow: 0 5px 5px #f2f6fc;
  transition: 0.25s;
  display: none;
}
.page_content_main .markdown-toc.open-list:before,
#toc-pos .markdown-toc.open-list:before {
  border: 1px solid #409eff;
  color: #409eff;
  border-radius: 50%;
  display: none;
}
.page_content_main .markdown-toc > .markdown-toc-list,
#toc-pos .markdown-toc > .markdown-toc-list {
  position: relative;
  z-index: 999;
  margin: 0;
  list-style: none;
  min-width: 150px;
  max-width: 230px;
  padding: 5px 0;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.05);
  border-radius: 8px;
  box-shadow: 0 0 2px #0000001a;
  max-height: calc(100vh - 350px);
  overflow-y: auto;
  transform: scale(0);
  margin-right: -230px;
  margin-bottom: -270px;
  opacity: 0;
  visibility: hidden;
  transform-origin: bottom right;
  transition: 0.25s ease 0s, margin-right 0s ease 0.25s,
    margin-bottom 0s ease 0.25s;
}
.page_content_main .markdown-toc.open-list .markdown-toc-list,
#toc-pos .markdown-toc.open-list .markdown-toc-list {
  margin-right: 0px;
  margin-bottom: 0px;
  transform: scale(1) translateY(-44px);
  opacity: 1;
  visibility: visible;
  transition: 0.5s cubic-bezier(0.4, 1.7, 0.6, 1), margin-right 0s,
    margin-bottom 0s;
}
.page_content_main .markdown-toc li,
#toc-pos .markdown-toc li {
  list-style: none !important;
}
.page_content_main .markdown-toc li a,
#toc-pos .markdown-toc li a {
  display: block;
  padding: 3px 15px;
  font-size: 12px;
  color: #343a40;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  transition: 0.15s;
}
.page_content_main .markdown-toc li a.current,
#toc-pos .markdown-toc li a.current {
  background: #ecf5ff;
  color: #409eff;
  box-shadow: 2px 0px #409eff inset;
}
.page_content_main .markdown-toc li a:hover,
#toc-pos .markdown-toc li a.current {
  background: #d9ecff;
  text-decoration: none;
  color: #409eff;
  transition: 0s;
}

.page_content_main .markdown-toc li ul,
#toc-pos .markdown-toc li a.current {
  padding-left: 15px;
}
</style>
