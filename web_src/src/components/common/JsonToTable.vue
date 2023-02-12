<template>
  <div>
    <SDialog
      v-if="dialogFormVisible"
      :title="$t('json_to_table')"
      :onCancel="callback"
      :onOK="transform"
    >
      <el-form>
        <el-input
          type="textarea"
          class="dialoContent"
          :placeholder="$t('json_to_table_description')"
          :rows="10"
          v-model="content"
        ></el-input>
      </el-form>
    </SDialog>
  </div>
</template>

<script>
export default {
  name: 'JsonToTable',
  props: {
    formLabelWidth: '120px',
    callback: () => {}
  },
  data() {
    return {
      content: '',
      json_table_data: '',
      dialogFormVisible: true
    }
  },
  methods: {
    transform() {
      try {
        var jsonData = JSON.parse(this.content)
        this.json_table_data =
          '|参数|类型|描述|\n|:-------|:-------|:-------|\n'
        const dd = this.tranJsonDesc(jsonData, [], true)
        if (dd) {
          for (let index = 0; index < dd.length; index++) {
            const element = dd[index]
            this.json_table_data += `|${element.name}|${element.type}| |\n`
          }
        }
        this.callback(this.json_table_data)
      } catch (e) {
        console.log(e)
        this.$alert('Json解析失败')
      }
    },
    // 将嵌套json转化为二维json，方便用于写参数描述
    // oJson为嵌套json。oJsonDesc是旧描述数据，方便新生产的描述里带上原旧描述
    // 参数hideLevel2指的是是否显示第一层前缀。一般 返回描述说明就隐藏。而请求boby的json则显示
    tranJsonDesc(oJson, oJsonDesc = [], showLevel1 = false) {
      let params = []
      const unique_keys = [] // unique_keys这个数组是用来判断重复的
      // 定义一个获取key的函数
      // 参数data为数组或者json对象。parentKey为这个对象的父key（假如存在的话）
      const getKeys = (data, parentKey = '', level = 1) => {
        const keys = Object.keys(data)
        keys.map(key => {
          let name = key
          // 如果是第3层及以上，parentKey存在且parentKey为非数字值
          if ((showLevel1 || level > 2) && parentKey && isNaN(parentKey)) {
            if (isNaN(key)) {
              name = parentKey + '.' + key
            } else {
              name = parentKey
            }
          }
          if (unique_keys.indexOf(name) > -1) {
            return false
          }

          if (typeof data[key] === 'object') {
            if (isNaN(key)) {
              params.push({
                name: name,
                type: Array.isArray(data[key]) ? 'array' : 'object',
                require: '1',
                remark: '',
                value: ''
              })
            }
            if (data[key]) getKeys(data[key], name, level + 1)
          } else {
            params.push({
              name: name,
              type: 'string',
              require: '1',
              remark: '',
              value: data[key]
            })
          }
          unique_keys.push(name)
        })
      }

      let data = oJson
      try {
        getKeys(data)
        if (params && params.length > 0) {
          // 定义一个去重函数
          const unique = arr => {
            return Array.from(new Set(arr))
          }

          if (oJsonDesc) {
            // 定义一个从原来的参数组中获取注释和类型的函数
            const getOld = name => {
              let obj = {}
              const oldList = oJsonDesc
              oldList.map(element => {
                if (element.name == name) {
                  obj = element
                }
              })
              return obj
            }
            params = unique(params)
            for (let index = 0; index < params.length; index++) {
              const obj = getOld(params[index].name)
              if (obj && obj.name) {
                params[index] = {
                  name: obj.name,
                  type: obj.type,
                  require: obj.require,
                  remark: obj.remark,
                  value: obj.value
                }
              }
            }
          }

          return unique(params)
        }
      } catch (e) {
        // 非json数据
        console.log(e)
        console.log('不是json数据')
        return oJson
      }
    },
    // json格式化与压缩
    // compress=false的时候表示美化json，compress=true的时候表示将美化过的json压缩还原
    formatJson(txt, compress = false) {
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
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped></style>
