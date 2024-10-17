const itemMenuDataToTreeData = menu => {
  const ItemMenuData = menu
  if (!ItemMenuData) return []
  const treeData = []
  // 如果根目录的页面
  if (ItemMenuData.pages) {
    ItemMenuData.pages.map(value => {
      let methods = ''
      let type = 'page'
      treeData.push({
        title: value.page_title,
        id: `page_${value.page_id}`,
        page_id: value.page_id,
        page_cat_id: 0,
        key: `page_${value.page_id}`,
        type: type,
        methods: methods
      })
    })
  }

  // 先定义一个递归处理目录的函数
  const doCat = (catData, parent_cat_id = 0) => {
    let catData2 = []
    for (let index = 0; index < catData.length; index++) {
      let oneCat = {
        children: [],
        title: catData[index].cat_name,
        id: `cat_${catData[index].cat_id}`,
        cat_id: catData[index].cat_id,
        parent_cat_id: parent_cat_id,
        key: `cat_${catData[index].cat_id}`,
        type: 'folder'
      }
      // 如果存在页面的话，则处理页面
      if (catData[index].pages.length > 0) {
        oneCat.children = []
        for (let k = 0; k < catData[index].pages.length; k++) {
          let methods = ''
          let type = 'page'
          oneCat.children.push({
            title: catData[index].pages[k].page_title,
            id: `page_${catData[index].pages[k].page_id}`,
            page_id: catData[index].pages[k].page_id,
            page_cat_id: catData[index].pages[k].cat_id,
            key: `page_${catData[index].pages[k].page_id}`,
            type: type,
            methods: methods
          })
        }
      }
      // 如果存在子目录的话，则递归处理子目录
      if (catData[index].catalogs.length > 0) {
        const tmpCatalogs = doCat(
          catData[index].catalogs,
          catData[index].cat_id
        )
        oneCat.children = oneCat.children.concat(tmpCatalogs)
      }
      catData2.push(oneCat)
    }
    return catData2
  } // 递归处理目录的函数定义完毕

  const catalogs = doCat(ItemMenuData.catalogs)
  catalogs.map(value => {
    treeData.push(value)
  })
  // console.log(treeData);
  return treeData
}

// 根据page_id ，获取树状数据的目录id们
const getParentIds = (tree, pageId) => {
  for (let i = 0; i < tree.length; i++) {
    const node = tree[i]
    if (node.page_id && node.page_id == pageId) {
      return []
    }
    if (node.children && node.children.length > 0) {
      const parentIds = getParentIds(node.children, pageId)
      if (parentIds !== null) {
        parentIds.push(`cat_${node.cat_id}`)
        return parentIds
      }
    }
  }
  return null
}

export { itemMenuDataToTreeData, getParentIds }
