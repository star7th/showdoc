/**
 * ShowDoc 离线HTML导出 - 前端交互逻辑
 */

;(function () {
  'use strict'

  // 工具函数
  const utils = {
    debounce: function (func, wait) {
      let timeout
      return function (...args) {
        clearTimeout(timeout)
        timeout = setTimeout(() => func.apply(this, args), wait)
      }
    },

    escapeHtml: function (text) {
      const div = document.createElement('div')
      div.textContent = text
      return div.innerHTML
    },

    getQueryParam: function (name) {
      const urlParams = new URLSearchParams(window.location.search)
      return urlParams.get(name)
    },
  }

  // 判断是否在 pages 目录下
  const isPageContext = function () {
    try {
      return window.location.pathname.indexOf('/pages/') !== -1
    } catch (e) {
      return false
    }
  }

  // 目录树渲染
  const CatalogTree = {
    init: function () {
      if (!window.PROJECT_DATA) {
        console.error('PROJECT_DATA not found')
        return
      }

      this.render()
      this.highlightCurrentPage()
      this.restoreExpandedState()
    },

    render: function () {
      const container = document.getElementById('catalogTree')
      if (!container) return

      const catalogs = window.PROJECT_DATA.catalogs || []
      const pages = window.PROJECT_DATA.pages || []

      // 构建目录树结构
      const tree = this.buildTree(catalogs, pages)

      // 渲染目录树
      container.innerHTML = this.renderTree(tree)
    },

    buildTree: function (catalogs, pages) {
      // 创建目录映射
      const catalogMap = {}
      catalogs.forEach((cat) => {
        catalogMap[cat.cat_id] = {
          ...cat,
          children: [],
          pages: [],
        }
      })

      // 构建树结构
      const root = []
      catalogs.forEach((cat) => {
        if (cat.parent_cat_id === 0) {
          root.push(catalogMap[cat.cat_id])
        } else {
          const parent = catalogMap[cat.parent_cat_id]
          if (parent) {
            parent.children.push(catalogMap[cat.cat_id])
          } else {
            root.push(catalogMap[cat.cat_id])
          }
        }
      })

      // 添加页面到对应目录
      pages.forEach((page) => {
        if (page.cat_id === 0) {
          // 根目录下的页面
          root.push({ type: 'page', ...page })
        } else {
          const catalog = catalogMap[page.cat_id]
          if (catalog) {
            catalog.pages.push(page)
          } else {
            root.push({ type: 'page', ...page })
          }
        }
      })

      // 排序
      root.sort((a, b) => {
        if (a.type === 'page' && b.type !== 'page') return 1
        if (a.type !== 'page' && b.type === 'page') return -1
        return (a.s_number || 0) - (b.s_number || 0)
      })

      return root
    },

    renderTree: function (items, level = 0) {
      let html = ''

      items.forEach((item) => {
        if (item.type === 'page') {
          // 渲染页面链接
          // 在首页使用 pages/page-xx.html；在页面内使用同目录相对路径 page-xx.html
          const href = isPageContext()
            ? `page-${item.page_id}.html`
            : `pages/page-${item.page_id}.html`
          html += `<a href="${href}" class="page-link" data-page-id="${
            item.page_id
          }">${utils.escapeHtml(item.page_title)}</a>`
        } else {
          // 渲染目录
          const catalogId = `cat-${item.cat_id}`
          const hasChildren =
            (item.children && item.children.length > 0) ||
            (item.pages && item.pages.length > 0)

          html += `<div class="catalog-item">`
          html += `<div class="catalog-link" data-catalog-id="${item.cat_id}">`

          // 即使空目录也显示图标，但使用不同的样式标识
          if (hasChildren) {
            html += `<span class="catalog-icon collapsed" data-target="${catalogId}"></span>`
          } else {
            // 空目录显示一个横线图标，表示没有内容
            html += `<span class="catalog-icon empty" style="opacity: 0.3; cursor: default;" title="空目录"></span>`
          }

          html += `<span>${utils.escapeHtml(item.cat_name)}</span>`
          html += `</div>`

          // 即使空目录也创建catalog-children容器，但内容为空，这样点击时至少有个反馈
          html += `<div class="catalog-children" id="${catalogId}" style="${
            hasChildren ? '' : 'display: none;'
          }">`

          if (hasChildren) {
            // 渲染子目录
            if (item.children && item.children.length > 0) {
              item.children.sort(
                (a, b) => (a.s_number || 0) - (b.s_number || 0)
              )
              html += this.renderTree(item.children, level + 1)
            }

            // 渲染页面
            if (item.pages && item.pages.length > 0) {
              item.pages.sort((a, b) => (a.s_number || 0) - (b.s_number || 0))
              item.pages.forEach((page) => {
                // 在首页使用 pages/page-xx.html；在页面内使用同目录相对路径 page-xx.html
                const href = isPageContext()
                  ? `page-${page.page_id}.html`
                  : `pages/page-${page.page_id}.html`
                html += `<a href="${href}" class="page-link" data-page-id="${
                  page.page_id
                }">${utils.escapeHtml(page.page_title)}</a>`
              })
            }
          } else {
            // 空目录显示提示信息（可选，如果不需要可以删除这行）
            // html += `<div style="padding: 5px 10px; color: #999; font-size: 12px;">（空目录）</div>`
          }

          html += `</div>`
          html += `</div>`
        }
      })

      return html
    },

    highlightCurrentPage: function () {
      // 使用字符串，避免大整数精度丢失问题
      const currentPageId = String(window.CURRENT_PAGE_ID || '0')

      // 移除所有active类
      document
        .querySelectorAll('.page-link.active, .catalog-link.active')
        .forEach((el) => {
          el.classList.remove('active')
        })

      if (currentPageId !== '0') {
        // 高亮当前页面（data-page-id 属性是字符串）
        const pageLink = document.querySelector(
          `.page-link[data-page-id="${currentPageId}"]`
        )
        if (pageLink) {
          pageLink.classList.add('active')

          // 展开父目录
          let parent = pageLink.parentElement
          while (parent) {
            if (parent.classList.contains('catalog-children')) {
              parent.classList.add('expanded')
              const icon =
                parent.previousElementSibling?.querySelector('.catalog-icon')
              if (icon) {
                icon.classList.remove('collapsed')
                icon.classList.add('expanded')
              }
            }
            parent = parent.parentElement
          }
        }
      }
    },

    restoreExpandedState: function () {
      const saved = localStorage.getItem('showdoc_catalog_expanded')
      if (!saved) return

      try {
        const expandedIds = JSON.parse(saved)
        expandedIds.forEach((catId) => {
          const children = document.getElementById(`cat-${catId}`)
          if (children) {
            children.classList.add('expanded')
            const icon =
              children.previousElementSibling?.querySelector('.catalog-icon')
            if (icon) {
              icon.classList.remove('collapsed')
              icon.classList.add('expanded')
            }
          }
        })
      } catch (e) {
        console.error('Failed to restore expanded state', e)
      }
    },

    saveExpandedState: function () {
      const expanded = []
      document.querySelectorAll('.catalog-children.expanded').forEach((el) => {
        const catId = el.id.replace('cat-', '')
        if (catId) {
          expanded.push(catId)
        }
      })
      localStorage.setItem('showdoc_catalog_expanded', JSON.stringify(expanded))
    },
  }

  // 搜索功能
  const Search = {
    init: function () {
      const searchInput = document.getElementById('searchInput')
      const searchResults = document.getElementById('searchResults')

      if (!searchInput || !searchResults) return

      // 防抖搜索
      const debouncedSearch = utils.debounce((query) => {
        this.performSearch(query)
      }, 300)

      searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim()
        if (query.length > 0) {
          debouncedSearch(query)
        } else {
          searchResults.classList.remove('show')
        }
      })

      // 点击外部关闭搜索结果
      document.addEventListener('click', (e) => {
        if (
          !searchInput.contains(e.target) &&
          !searchResults.contains(e.target)
        ) {
          searchResults.classList.remove('show')
        }
      })

      // 键盘快捷键
      document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
          e.preventDefault()
          searchInput.focus()
        }
        if (e.key === 'Escape') {
          searchResults.classList.remove('show')
          searchInput.blur()
        }
      })
    },

    performSearch: function (query) {
      if (!window.SEARCH_INDEX) {
        console.error('SEARCH_INDEX not found')
        return
      }

      const results = []
      const lowerQuery = query.toLowerCase()

      window.SEARCH_INDEX.forEach((item) => {
        const title = item.page_title || ''
        const content = item.content_preview || ''

        const titleMatch = title.toLowerCase().indexOf(lowerQuery) !== -1
        const contentMatch = content.toLowerCase().indexOf(lowerQuery) !== -1

        if (titleMatch || contentMatch) {
          let score = 0
          if (titleMatch) score += 10
          if (contentMatch) score += 1

          results.push({
            ...item,
            score: score,
          })
        }
      })

      // 按分数排序
      results.sort((a, b) => b.score - a.score)

      this.displayResults(results.slice(0, 10), query)
    },

    displayResults: function (results, query) {
      const searchResults = document.getElementById('searchResults')
      if (!searchResults) return

      if (results.length === 0) {
        searchResults.innerHTML =
          '<div class="search-result-item">未找到匹配结果</div>'
        searchResults.classList.add('show')
        return
      }

      let html = ''
      results.forEach((result) => {
        // 在首页使用 pages/page-xx.html；在页面内使用同目录相对路径 page-xx.html
        const href = isPageContext()
          ? `page-${result.page_id}.html`
          : `pages/page-${result.page_id}.html`
        const title = this.highlightText(result.page_title, query)
        const preview = this.highlightText(result.content_preview, query)

        html += `
          <div class="search-result-item" onclick="window.location.href='${href}'">
            <div class="search-result-title">${title}</div>
            <div class="search-result-preview">${preview}</div>
          </div>
        `
      })

      searchResults.innerHTML = html
      searchResults.classList.add('show')
    },

    highlightText: function (text, query) {
      if (!query) return utils.escapeHtml(text)

      const regex = new RegExp(`(${query})`, 'gi')
      return utils.escapeHtml(text).replace(regex, '<mark>$1</mark>')
    },
  }

  // 页面导航
  const PageNav = {
    init: function () {
      // 使用字符串比较，避免大整数精度丢失问题
      const currentPageId = String(window.CURRENT_PAGE_ID || '0')
      if (currentPageId === '0') return

      if (!window.PROJECT_DATA) return

      const pages = window.PROJECT_DATA.pages || []
      if (pages.length === 0) return

      // 按s_number排序
      const sortedPages = [...pages].sort((a, b) => {
        if (a.cat_id !== b.cat_id) {
          return a.cat_id - b.cat_id
        }
        return (a.s_number || 0) - (b.s_number || 0)
      })

      // 使用字符串比较，避免大整数精度丢失
      const currentIndex = sortedPages.findIndex(
        (p) => String(p.page_id) === currentPageId
      )
      if (currentIndex === -1) return

      const prevPage = currentIndex > 0 ? sortedPages[currentIndex - 1] : null
      const nextPage =
        currentIndex < sortedPages.length - 1
          ? sortedPages[currentIndex + 1]
          : null

      this.render(prevPage, nextPage)
    },

    render: function (prevPage, nextPage) {
      const container = document.getElementById('pageNav')
      if (!container) return

      let html = '<div>'

      if (prevPage) {
        // 在页面文件中，使用同目录的相对路径
        const href = `page-${prevPage.page_id}.html`
        html += `<a href="${href}" class="page-nav-link">← 上一页: ${utils.escapeHtml(
          prevPage.page_title
        )}</a>`
      } else {
        html += '<span class="page-nav-link disabled">← 上一页</span>'
      }

      html += '</div><div>'

      if (nextPage) {
        // 在页面文件中，使用同目录的相对路径
        const href = `page-${nextPage.page_id}.html`
        html += `<a href="${href}" class="page-nav-link">下一页: ${utils.escapeHtml(
          nextPage.page_title
        )} →</a>`
      } else {
        html += '<span class="page-nav-link disabled">下一页 →</span>'
      }

      html += '</div>'

      container.innerHTML = html
    },
  }

  // 响应式菜单
  const Responsive = {
    init: function () {
      const menuToggle = document.getElementById('menuToggle')
      const sidebar = document.getElementById('sidebar')

      if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
          sidebar.classList.toggle('open')
        })
      }

      // 点击侧边栏外部关闭
      document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
          if (sidebar && sidebar.classList.contains('open')) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
              sidebar.classList.remove('open')
            }
          }
        }
      })
    },
  }

  // 目录树点击事件
  const bindCatalogEvents = function () {
    document.addEventListener('click', (e) => {
      // 点击箭头图标
      const icon = e.target.closest('.catalog-icon')
      if (icon && icon.dataset.target) {
        e.preventDefault()
        e.stopPropagation()

        const targetId = icon.dataset.target
        const children = document.getElementById(targetId)

        if (children) {
          children.classList.toggle('expanded')
          icon.classList.toggle('collapsed')
          icon.classList.toggle('expanded')

          CatalogTree.saveExpandedState()
        }
        return
      }

      // 点击目录整行（名称也可触发展开/折叠）
      const link = e.target.closest('.catalog-link')
      if (link && link.dataset.catalogId) {
        e.preventDefault()
        e.stopPropagation()

        const targetId = `cat-${link.dataset.catalogId}`
        const children = document.getElementById(targetId)
        const iconInLink = link.querySelector('.catalog-icon')

        if (children) {
          // 检查是否是空目录（没有子元素）
          const isEmpty = children.children.length === 0
          
          if (isEmpty) {
            // 空目录点击时不执行展开/折叠，但可以给一个视觉反馈
            // 可以添加一个短暂的样式变化提示这是空目录
            link.style.opacity = '0.6'
            setTimeout(() => {
              link.style.opacity = '1'
            }, 200)
          } else {
            // 有内容的目录正常展开/折叠
            children.classList.toggle('expanded')
            if (iconInLink && !iconInLink.classList.contains('empty')) {
              iconInLink.classList.toggle('collapsed')
              iconInLink.classList.toggle('expanded')
            }
            CatalogTree.saveExpandedState()
          }
        }
      }
    })
  }

  // 初始化
  document.addEventListener('DOMContentLoaded', function () {
    CatalogTree.init()
    Search.init()
    PageNav.init()
    Responsive.init()
    bindCatalogEvents()
  })
})()
