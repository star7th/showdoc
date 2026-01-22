import { defineStore } from 'pinia'

export interface ItemInfo {
  item_id?: string | number
  item_name?: string
  item_type?: number
  item_description?: string
  password?: string
  is_archived?: number
  addtime?: string
  last_update_time?: string
  creator_username?: string
  creator_uid?: number
  item_domain?: string
  is_del?: number
  current_page_id?: string | number
}

export const useItemStore = defineStore('item', {
  state: () => ({
    /** 当前项目信息 */
    itemInfo: {} as ItemInfo,
    /** 用于强制刷新组件的 key */
    itemKey: 1,
    /** 当前展开的目录 ID */
    openCatId: 0,
    /** 目录树数据 */
    catalogTree: [] as any[],
  }),

  getters: {
    /** 项目 ID */
    itemId: (state) => state.itemInfo?.item_id,
    /** 项目名称 */
    itemName: (state) => state.itemInfo?.item_name || '',
    /** 项目类型 */
    itemType: (state) => state.itemInfo?.item_type || 1,
  },

  actions: {
    /** 设置项目信息 */
    setItemInfo(info: ItemInfo) {
      this.itemInfo = info
    },

    /** 刷新项目（增加 key 触发组件重新渲染） */
    reloadItem() {
      this.itemKey++
    },

    /** 设置当前展开的目录 ID */
    setOpenCatId(catId: number) {
      this.openCatId = catId
    },

    /** 设置目录树数据 */
    setCatalogTree(tree: any[]) {
      this.catalogTree = tree
    },

    /** 清空项目信息 */
    clearItemInfo() {
      this.itemInfo = {}
      this.openCatId = 0
      this.catalogTree = []
    },
  },
})

