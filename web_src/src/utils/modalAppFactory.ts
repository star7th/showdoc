/**
 * 弹窗应用工厂
 * 统一管理弹窗组件的创建和销毁
 * 自动注册所有必要的组件和插件
 */

import { createApp, type App } from 'vue'
import { createPinia } from 'pinia'
import Antd from 'ant-design-vue'
import i18n from '@/i18n'

/**
 * 创建弹窗应用实例
 * 自动继承主应用的所有组件配置
 *
 * @param component - Vue 组件
 * @param props - 传递给组件的 props
 * @returns { app, mountNode } - Vue 实例和挂载节点
 */
export function createModalApp(
  component: any,
  props: any = {}
): { app: App; mountNode: HTMLDivElement } {
  // 创建挂载节点
  const mountNode = document.createElement('div')
  document.body.appendChild(mountNode)

  // 创建 Vue 实例
  const app = createApp(component, props)

  // 使用主应用的 Pinia store，实现状态共享
  const store = (window as any).__MAIN_APP_STORE__
  if (!store) {
    console.warn('主应用 Pinia store 未找到，将创建新的 store 实例')
    const newStore = createPinia()
    app.use(newStore)
  } else {
    app.use(store)
  }

  // 注册所有 Ant Design Vue 组件
  app.use(Antd)

  // 注册国际化
  app.use(i18n)

  // 注册 Router
  const router = (window as any).__MAIN_APP_ROUTER__
  if (router) {
    app.use(router)
  } else {
    console.warn('主应用 Router 未找到，弹窗中的路由功能可能不可用')
  }

  // 继承主应用的全局配置（如 emitter）
  app.config.globalProperties.emitter = (window as any).__MAIN_EMITTER__

  return { app, mountNode }
}

/**
 * 销毁弹窗应用实例
 *
 * @param app - Vue 实例
 * @param mountNode - 挂载节点
 */
export function destroyModalApp(app: App, mountNode: HTMLDivElement) {
  app.unmount()
  mountNode.remove()
}

