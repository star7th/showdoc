// 将后端返回的菜单数据转换为树形数据
export const itemMenuDataToTreeData = (menu: any): any[] => {
  if (!menu) return []

  const treeData: any[] = []

  // 处理页面（根目录页面）
  if (menu.pages && Array.isArray(menu.pages) && menu.pages.length > 0) {
    menu.pages.forEach((page: any) => {
      treeData.push({
        title: page.page_title,
        id: `page_${page.page_id}`,
        page_id: page.page_id,
        page_cat_id: 0,
        key: `page_${page.page_id}`,
        type: 'page',
        is_draft: page.is_draft
      })
    })
  }

  // 递归处理目录
  const doCat = (catData: any[], parent_cat_id: number = 0): any[] => {
    const result: any[] = []

    for (let index = 0; index < catData.length; index++) {
      const oneCat = {
        children: [] as any[],
        title: catData[index].cat_name,
        id: `cat_${catData[index].cat_id}`,
        cat_id: catData[index].cat_id,
        parent_cat_id: parent_cat_id,
        key: `cat_${catData[index].cat_id}`,
        type: 'folder'
      }

      // 如果目录下有页面，则添加页面
      if (catData[index].pages && Array.isArray(catData[index].pages) && catData[index].pages.length > 0) {
        for (let k = 0; k < catData[index].pages.length; k++) {
          oneCat.children.push({
            title: catData[index].pages[k].page_title,
            id: `page_${catData[index].pages[k].page_id}`,
            page_id: catData[index].pages[k].page_id,
            page_cat_id: catData[index].cat_id,
            key: `page_${catData[index].pages[k].page_id}`,
            type: 'page',
            is_draft: catData[index].pages[k].is_draft
          })
        }
      }

      // 如果存在子目录的话，则递归处理子目录
      if (catData[index].catalogs && Array.isArray(catData[index].catalogs) && catData[index].catalogs.length > 0) {
        const tmpCatalogs = doCat(
          catData[index].catalogs,
          catData[index].cat_id
        )
        oneCat.children = oneCat.children.concat(tmpCatalogs)
      }

      result.push(oneCat)
    }

    return result
  }

  // 处理目录
  if (menu.catalogs && Array.isArray(menu.catalogs) && menu.catalogs.length > 0) {
    const catalogs = doCat(menu.catalogs, 0)
    catalogs.forEach((value) => {
      treeData.push(value)
    })
  }

  return treeData
}

// 根据page_id获取所有父级目录ID（用于展开路径）
export const getParentIds = (nodes: any[], pageId: number): string[] => {
  const path: string[] = []

  const findPath = (nodes: any[], targetId: number, currentPath: string[]): boolean => {
    for (const node of nodes) {
      if (node.type === 'page' && node.page_id === targetId) {
        return true
      }

      if (node.children && node.children.length > 0) {
        const newPath = [...currentPath]
        if (node.type === 'folder') {
          newPath.push(node.key)
        }

        if (findPath(node.children, targetId, newPath)) {
          path.push(...newPath)
          return true
        }
      }
    }
    return false
  }

  findPath(nodes, pageId, [])
  return path
}

// 根据cat_id获取所有父级目录ID（用于展开路径）
export const getParentCatIds = (nodes: any[], catId: number): string[] => {
  const path: string[] = []

  const findPath = (nodes: any[], targetCatId: number, currentPath: string[]): boolean => {
    for (const node of nodes) {
      if (node.type === 'folder' && node.cat_id === targetCatId) {
        path.push(...currentPath)
        return true
      }

      if (node.children && node.children.length > 0) {
        const newPath = [...currentPath]
        if (node.type === 'folder') {
          newPath.push(node.key)
        }

        if (findPath(node.children, targetCatId, newPath)) {
          return true
        }
      }
    }
    return false
  }

  findPath(nodes, catId, [])
  return path
}

// 获取所有目录的key（用于全部展开）
export const getAllCatKeys = (nodes: any[]): string[] => {
  const keys: string[] = []

  const traverse = (nodes: any[]) => {
    nodes.forEach(node => {
      if (node.type === 'folder') {
        keys.push(node.key)
      }
      if (node.children && node.children.length > 0) {
        traverse(node.children)
      }
    })
  }

  traverse(nodes)
  return keys
}

// 查找节点
export const findNode = (nodes: any[], key: string): any => {
  for (const node of nodes) {
    if (node.key === key) {
      return node
    }
    if (node.children && node.children.length > 0) {
      const found = findNode(node.children, key)
      if (found) {
        return found
      }
    }
  }
  return null
}
