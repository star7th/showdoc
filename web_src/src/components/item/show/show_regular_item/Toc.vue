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
          that.toc_main_script()
        }
      } catch (e) {}
    }, 200)
  },
  methods: {
    toc_main_script() {
      // 监听点击事件并滑动到相应位置
      $(document).on('click', '.markdown-toc-list a[href]', function(event) {
        event.preventDefault()
        var name = $(this)
          .attr('href')
          .substring(1)
        var top_at_window = $('[name="' + name + '"]').offset().top
        $('html, body').animate({ scrollTop: top_at_window }, 300)
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
            1
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
  top: 230px;
  margin-left: 800px;
  min-width: 32px;
  min-height: 32px;
  cursor: pointer;
  z-index: 1;
}
.page_content_main .markdown-toc:before {
  display: none; /*先隐藏*/
  content: '\e63f';
  font-family: element-icons !important;
  color: #909399;
  position: absolute;
  top: 0;
  width: 32px;
  height: 32px;
  line-height: 32px;
  text-align: center;
  background: #fafafa;
  border: 1px solid #dcdfe6;
  border-radius: 5px;
  cursor: pointer;
  box-shadow: 0 5px 5px #f2f6fc;
  transition: 0.25s;
}
.page_content_main .markdown-toc.open-list:before {
  border: 1px solid #dcdfe6;
  color: #909399;
  border-radius: 50%;
}
.page_content_main .markdown-toc > .markdown-toc-list {
  position: relative;
  z-index: 999;
  margin: 0;
  margin-top: 20px;
  list-style: none;
  min-width: 150px;
  max-width: 200px;
  padding: 5px 0;
  background: #fafafa;
  border: 1px solid #dcdfe6;
  border-radius: 5px;
  box-shadow: 0 5px 5px #f2f6fc;
  max-height: 320px;
  overflow-y: auto;
  transform: scale(0);
  margin-right: -230px;
  margin-bottom: -270px;
  opacity: 0;
  visibility: hidden;
  transform-origin: top left;
  transition: 0.25s ease 0s, margin-right 0s ease 0.25s,
    margin-bottom 0s ease 0.25s;
}
.page_content_main .markdown-toc.open-list .markdown-toc-list {
  margin-right: 0px;
  margin-bottom: 0px;
  transform: scale(1) translateY(-44px);
  opacity: 1;
  visibility: visible;
  transition: 0.5s cubic-bezier(0.4, 1.7, 0.6, 1), margin-right 0s,
    margin-bottom 0s;
}
.page_content_main .markdown-toc li {
  list-style: none !important;
}
.page_content_main .markdown-toc li a {
  display: block;
  padding: 3px 15px;
  font-size: 12px;
  color: #606266;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  transition: 0.15s;
}
.page_content_main .markdown-toc li a.current {
  background: #ecf5ff;
  color: #40a9ff;
  box-shadow: 2px 0px #40a9ff inset;
}
.page_content_main .markdown-toc li a:hover {
  background: #d9ecff;
  text-decoration: none;
  color: #40a9ff;
  transition: 0s;
}
.page_content_main .markdown-toc li ul {
  padding: 0;
}
.page_content_main .markdown-toc li li a {
  padding-left: 30px;
}
</style>
