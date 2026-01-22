/**
 * 通用工具函数
 */

/**
 * 本地存储工具
 */
export const storage = {
  get: (key: string, orNot: any = null) => {
    const value = localStorage.getItem(key)
    try {
      return value ? JSON.parse(value) : orNot
    } catch (e) {
      console.log('storage catch', e, orNot)
      return orNot
    }
  },
  set: (key: string, value: any) => {
    localStorage.setItem(key, JSON.stringify(value))
  },
  remove: (key: string) => {
    localStorage.removeItem(key)
  },
  clear: () => {
    localStorage.clear()
  },
}

/**
 * 延迟函数
 */
export function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => {
    setTimeout(resolve, ms)
  })
}

/**
 * JSON 格式化与压缩
 * @param txt - JSON 字符串或对象
 * @param compress - 是否压缩
 */
export const formatJson = (txt: any, compress = false): string => {
  if (compress === false) {
    try {
      if (typeof txt === 'string') {
        txt = JSON.parse(txt)
      }
      return JSON.stringify(txt, null, 2)
    } catch (e) {
      return txt
    }
  }

  try {
    const obj = JSON.parse(txt)
    return JSON.stringify(obj)
  } catch (e) {
    return txt
  }
}

/**
 * 判断是否是 JSON 对象或 JSON 字符串
 */
export const isJson = (str: any): boolean => {
  if (str) {
    try {
      if (typeof str === 'object') {
        if (JSON.stringify(str)) {
          return true
        }
      }
      if (typeof JSON.parse(str) === 'object') {
        return true
      }
    } catch (e) {
      // 不是 JSON
    }
  }
  return false
}

/**
 * HTML 反转义函数
 */
export const unescapeHTML = (str: string): string =>
  str.replace(
    /&amp;|&lt;|&gt;|&#39;|&quot;|&#039;/g,
    (tag) =>
      ({
        '&amp;': '&',
        '&lt;': '<',
        '&gt;': '>',
        '&#39;': "'",
        '&#039;': "'",
        '&quot;': '"',
      }[tag] || tag)
  )

/**
 * 防抖函数
 */
export function debounce<T extends (...args: any[]) => any>(
  fn: T,
  delay: number
): (...args: Parameters<T>) => void {
  let timer: ReturnType<typeof setTimeout> | null = null
  return function (...args: Parameters<T>) {
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => {
      fn.apply(null, args)
    }, delay)
  }
}

/**
 * 节流函数
 */
export function throttle<T extends (...args: any[]) => any>(
  fn: T,
  delay: number
): (...args: Parameters<T>) => void {
  let lastTime = 0
  return function (...args: Parameters<T>) {
    const now = Date.now()
    if (now - lastTime >= delay) {
      lastTime = now
      fn.apply(null, args)
    }
  }
}

/**
 * 复制文本到剪贴板
 */
export async function copyToClipboard(text: string): Promise<boolean> {
  try {
    if (navigator.clipboard) {
      await navigator.clipboard.writeText(text)
      return true
    } else {
      // 兼容旧浏览器
      const textarea = document.createElement('textarea')
      textarea.value = text
      textarea.style.position = 'fixed'
      textarea.style.opacity = '0'
      document.body.appendChild(textarea)
      textarea.select()
      document.execCommand('copy')
      document.body.removeChild(textarea)
      return true
    }
  } catch (e) {
    console.error('复制失败:', e)
    return false
  }
}

/**
 * 生成唯一 ID
 */
export function generateId(): string {
  return Date.now().toString(36) + Math.random().toString(36).substring(2)
}

