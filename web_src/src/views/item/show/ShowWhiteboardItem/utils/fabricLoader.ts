/**
 * Fabric.js 动态加载器
 * 从静态资源目录加载,不打包进 npm
 */

import { ref } from 'vue'
import { getStaticPath } from '@/utils/system'

export function useFabricLoader() {
  const isLoaded = ref(false)
  const isLoading = ref(false)

  /**
   * 动态加载 Fabric.js
   */
  const loadFabric = (): Promise<void> => {
    return new Promise((resolve, reject) => {
      // 如果已经加载,直接返回
      if ((window as any).fabric) {
        isLoaded.value = true
        resolve()
        return
      }

      if (isLoading.value) {
        // 等待加载完成
        const checkInterval = setInterval(() => {
          if ((window as any).fabric) {
            clearInterval(checkInterval)
            isLoaded.value = true
            resolve()
          }
        }, 100)
        return
      }

      isLoading.value = true

      const script = document.createElement('script')
      script.src = `${getStaticPath()}whiteboard/fabric.min.js`
      script.onload = () => {
        isLoaded.value = true
        isLoading.value = false
        resolve()
      }
      script.onerror = () => {
        isLoading.value = false
        reject(new Error('Failed to load Fabric.js'))
      }
      document.head.appendChild(script)
    })
  }

  return {
    isLoaded,
    isLoading,
    loadFabric
  }
}

