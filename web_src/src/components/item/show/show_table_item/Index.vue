<template>
  <div class="hello">
    <link href="static/xspreadsheet/xspreadsheet.css" rel="stylesheet" />
    <div id="header"></div>
    <div class="edit-bar" v-if="item_info.item_edit">
      <el-button type="primary" size="mini" @click="save">{{
        $t('save')
      }}</el-button>
      <el-dropdown @command="dropdownCallback">
        <el-button size="mini">
          {{ $t('more') }}
          <i class="el-icon-arrow-down el-icon--right"></i>
        </el-button>
        <el-dropdown-menu slot="dropdown">
          <el-dropdown-item :command="shareItem">{{
            $t('share')
          }}</el-dropdown-item>
          <router-link
            :to="'/item/setting/' + item_info.item_id"
            v-if="item_info.item_manage"
          >
            <el-dropdown-item>{{ $t('item_setting') }}</el-dropdown-item>
          </router-link>
          <el-dropdown-item
            :command="
              () => {
                importDialogVisible = true
              }
            "
            >{{ $t('import_file') }}</el-dropdown-item
          >
          <el-dropdown-item :command="exportFile">{{
            $t('export')
          }}</el-dropdown-item>
          <el-dropdown-item :command="goback">{{
            $t('goback')
          }}</el-dropdown-item>
        </el-dropdown-menu>
      </el-dropdown>
    </div>
    <div class="edit-bar" v-if="!item_info.item_edit">
      <el-button size="mini" @click="goback">{{ $t('goback') }}</el-button>
    </div>
    <div id="table-item"></div>
    <el-dialog
      :title="$t('share')"
      :visible.sync="dialogVisible"
      width="600px"
      :close-on-click-modal="false"
      class="text-center"
    >
      <p>
        {{ $t('item_address') }} :
        <code>{{ share_item_link }}</code>
      </p>
      <p>
        <a
          href="javascript:;"
          class="home-phone-butt"
          v-clipboard:copyhttplist="copyText"
          v-clipboard:success="onCopy"
          >{{ $t('copy_link') }}</a
        >
      </p>
      <p style="border-bottom: 1px solid #eee;">
        <img id style="width:114px;height:114px;" :src="qr_item_link" />
      </p>
      <span slot="footer" class="dialog-footer">
        <el-button type="primary" @click="dialogVisible = false">{{
          $t('confirm')
        }}</el-button>
      </span>
    </el-dialog>

    <el-dialog
      :title="$t('import_excel')"
      :visible.sync="importDialogVisible"
      width="600px"
      :close-on-click-modal="false"
      class="text-center"
    >
      <p>
        <input
          type="file"
          name="xlfile"
          id="xlf"
          @change="
            e => {
              improtFile(e.target.files)
            }
          "
        />
      </p>
      <span slot="footer" class="dialog-footer">
        <el-button type="primary" @click="importDialogVisible = false">{{
          $t('confirm')
        }}</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<style scoped>
.edit-bar {
  position: absolute;
  right: 10px;
  margin-top: 5px;
}
.edit-bar > button {
  margin-right: 10px;
}
</style>

<script>
if (typeof window !== 'undefined') {
  var $s = require('scriptjs')
}
export default {
  props: {
    item_info: ''
  },
  data() {
    return {
      menu: '',
      content: '',
      page_title: '',
      page_id: '',
      dialogVisible: false,
      share_item_link: '',
      qr_item_link: '',
      copyText: '',
      spreadsheetObj: {},
      spreadsheetData: {},
      isLock: 0,
      isEditable: 0,
      intervalId: 0,
      importDialogVisible: false
    }
  },
  components: {},
  methods: {
    getPageContent(page_id) {
      var that = this
      if (!page_id) {
        page_id = that.page_id
      }
      this.request('/api/page/info', {
        page_id: page_id
      }).then(response => {
        if (response.data.page_content) {
          let objData
          try {
            // 先定义一个html反转义的函数
            const unescapeHTML = str =>
              str.replace(
                /&amp;|&lt;|&gt;|&#39;|&quot;/g,
                tag =>
                  ({
                    '&amp;': '&',
                    '&lt;': '<',
                    '&gt;': '>',
                    '&#39;': "'",
                    '&quot;': '"'
                  }[tag] || tag)
              )
            objData = JSON.parse(unescapeHTML(response.data.page_content))
          } catch (error) {
            objData = {}
          }
          this.spreadsheetData = objData
          // 初始化表格
          this.initSheet()
          if (this.item_info.item_edit) {
            this.draft()
          }
        }
      })
    },
    initSheet() {
      if (!x_spreadsheet) return false
      let mode = this.isEditable ? 'edit' : 'read'
      document.getElementById('table-item').innerHTML = '' // 清空原来的东西
      this.spreadsheetObj = null

      // 初始化表格
      this.spreadsheetObj = x_spreadsheet('#table-item', {
        mode: mode, // edit | read
        showToolbar: true,
        row: {
          len: 500,
          height: 25
        }
      }).loadData(this.spreadsheetData) // load data
    },

    shareItem() {
      let path = this.item_info.item_domain
        ? this.item_info.item_domain
        : this.item_info.item_id
      this.share_item_link = this.getRootPath() + '#/' + path
      this.qr_item_link =
        DocConfig.server +
        '/api/common/qrcode&size=3&url=' +
        encodeURIComponent(this.share_item_link)
      this.dialogVisible = true
      this.copyText =
        this.item_info.item_name + '  -- ShowDoc \r\n' + this.share_item_link
    },
    onCopy() {
      this.$message(this.$t('copy_success'))
    },
    save() {
      this.request('/api/page/save', {
        page_id: this.page_id,
        page_title: this.item_info.item_name,
        item_id: this.item_info.item_id,
        is_urlencode: 1,
        page_content: encodeURIComponent(
          JSON.stringify(this.spreadsheetObj.getData())
        )
      }).then(data => {
        // console.log(data)
        this.$message({
          showClose: true,
          message: '保存成功',
          type: 'success'
        })
        // 删除草稿
        this.deleteDraft()
      })
    },
    goback() {
      this.$router.push({
        path: '/item/index'
      })
      // 由于x_spreadsheet的固有缺陷，只能重新刷新销毁实例了
      setTimeout(() => {
        window.location.reload()
      }, 200)
    },
    dropdownCallback(data) {
      if (data) {
        data()
      }
    },
    // 草稿
    draft() {
      var that = this
      var pkey = 'page_content_' + this.page_id
      // 定时保存文本内容到localStorage
      setInterval(() => {
        var content = JSON.stringify(this.spreadsheetObj.getData())
        localStorage.setItem(pkey, content)
      }, 30 * 1000)

      // 检测是否有定时保存的内容
      var page_content = JSON.parse(localStorage.getItem(pkey))
      if (
        page_content &&
        page_content.length > 0 &&
        localStorage.getItem(pkey) !=
          JSON.stringify(this.spreadsheetObj.getData())
      ) {
        localStorage.removeItem(pkey)
        that
          .$confirm(that.$t('draft_tips'), '', {
            showClose: false
          })
          .then(() => {
            this.spreadsheetData = page_content
            // 初始化表格
            this.initSheet()
            localStorage.removeItem(pkey)
          })
          .catch(() => {
            localStorage.removeItem(pkey)
          })
      }
    },

    // 遍历删除草稿
    deleteDraft() {
      for (var i = 0; i < localStorage.length; i++) {
        var name = localStorage.key(i)
        if (name.indexOf('page_content_') > -1) {
          localStorage.removeItem(name)
        }
      }
    },
    // 锁定
    setLock() {
      if (this.page_id > 0) {
        this.request('/api/page/setLock', {
          page_id: this.page_id,
          item_id: this.item_info.item_id
        }).then(() => {
          this.isLock = 1
        })
      }
    },
    // 解除锁定
    unlock() {
      if (!this.isLock) {
        return // 本来处于未锁定中的话，不发起请求
      }
      this.request('/api/page/setLock', {
        page_id: this.page_id,
        item_id: this.item_info.item_id,
        lock_to: 1000
      }).then(() => {
        this.isLock = 0
      })
    },
    // 如果用户处于锁定状态的话，用心跳保持锁定
    heartBeatLock() {
      this.intervalId = setInterval(() => {
        if (this.isLock) {
          this.setLock()
        }
      }, 3 * 60 * 1000)
    },
    // 判断页面是否被锁定编辑
    remoteIsLock() {
      this.request('/api/page/isLock', {
        page_id: this.page_id
      }).then(res => {
        // 判断已经锁定了不
        if (res.data.lock > 0) {
          if (res.data.is_cur_user > 0) {
            this.isLock = 1
            this.isEditable = 1
            this.initSheet()
            this.heartBeatLock()
          } else {
            this.$alert(this.$t('locking') + res.data.lock_username)
            this.item_info.item_edit = false
            clearInterval(this.intervalId)
            this.deleteDraft()
          }
        } else {
          this.setLock() // 如果没有被别人锁定，则进编辑页面后自己锁定。
          this.isEditable = 1
          this.initSheet()
          this.heartBeatLock()
        }
      })
    },
    exportFile() {
      // 先定义一个函数
      const xtos = sdata => {
        var out = XLSX.utils.book_new()
        sdata.forEach(function(xws) {
          var aoa = [[]]
          var rowobj = xws.rows
          for (var ri = 0; ri < rowobj.len; ++ri) {
            var row = rowobj[ri]
            if (!row) continue
            aoa[ri] = []
            Object.keys(row.cells).forEach(function(k) {
              var idx = +k
              if (isNaN(idx)) return
              aoa[ri][idx] = row.cells[k].text
            })
          }
          var ws = XLSX.utils.aoa_to_sheet(aoa)
          XLSX.utils.book_append_sheet(out, ws, xws.name)
        })
        return out
      }

      /* build workbook from the grid data */
      var new_wb = xtos(this.spreadsheetObj.getData())

      /* generate download */
      XLSX.writeFile(new_wb, 'showdoc.xlsx')
    },
    improtFile(files) {
      const f = files[0]

      const stox = wb => {
        var out = []
        wb.SheetNames.forEach(function(name) {
          var o = { name: name, rows: {} }
          var ws = wb.Sheets[name]
          var aoa = XLSX.utils.sheet_to_json(ws, { raw: false, header: 1 })
          aoa.forEach(function(r, i) {
            var cells = {}
            r.forEach(function(c, j) {
              cells[j] = { text: c }
            })
            o.rows[i] = { cells: cells }
          })
          out.push(o)
        })
        return out
      }
      var reader = new FileReader()
      reader.onload = e => {
        var data = e.target.result
        var mdata = stox(XLSX.read(data, { type: 'array' }))

        if (mdata) {
          /* update x-spreadsheet */
          this.spreadsheetObj.loadData(mdata)
          this.importDialogVisible = false
        }
      }
      reader.readAsArrayBuffer(f)
    }
  },
  mounted() {
    this.menu = this.item_info.menu
    this.page_id = this.menu.pages[0].page_id

    // 加载依赖""
    $s([`static/xspreadsheet/xspreadsheet.js`], () => {
      $s(
        [
          `static/xspreadsheet/locale/zh-cn.js`,
          `static/xspreadsheet/locale/en.js`
        ],
        () => {
          if (DocConfig.lang == 'en') {
            x_spreadsheet.locale('en')
          } else {
            x_spreadsheet.locale('zh-cn')
          }
          this.getPageContent()

          if (this.item_info.item_edit) {
            this.remoteIsLock()
          }
        }
      )
      $s([`static/xspreadsheet/xlsx.full.min.js`])
    })
  },
  beforeDestroy() {
    this.$message.closeAll()
    clearInterval(this.intervalId)
    this.unlock()
  }
}
</script>
