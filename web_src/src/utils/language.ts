/**
 * 语言检测工具
 * 用于自动检测浏览器语言
 */

/**
 * 检测浏览器语言
 * 优先级：navigator.languages > navigator.language > navigator.userLanguage > fallback
 * @returns 检测到的语言代码 ('zh-CN' | 'en-US')
 */
export function detectBrowserLanguage(): 'zh-CN' | 'en-US' {
  // 1. 尝试从 navigator.languages 数组中匹配（最准确，包含用户语言偏好列表）
  const languages = navigator.languages || []
  for (const lang of languages) {
    const matched = matchSupportedLanguage(lang)
    if (matched) {
      return matched
    }
  }

  // 2. 尝试从 navigator.language 匹配
  const primaryLang = navigator.language
  if (primaryLang) {
    const matched = matchSupportedLanguage(primaryLang)
    if (matched) {
      return matched
    }
  }

  // 3. 尝试从 IE 特有的属性匹配（兼容旧浏览器）
  const userLang = (navigator as any).userLanguage
  const browserLang = (navigator as any).browserLanguage
  const systemLang = (navigator as any).systemLanguage

  for (const lang of [userLang, browserLang, systemLang]) {
    if (lang) {
      const matched = matchSupportedLanguage(lang)
      if (matched) {
        return matched
      }
    }
  }

  // 4. 都匹配不到，返回默认语言
  return 'zh-CN'
}

/**
 * 匹配浏览器语言到支持的语言
 * 支持精确匹配和语言族匹配
 * @param browserLang 浏览器返回的语言代码
 * @returns 匹配到的语言代码，如果不匹配则返回 null
 */
export function matchSupportedLanguage(
  browserLang: string
): 'zh-CN' | 'en-US' | null {
  if (!browserLang) return null

  const lang = browserLang.toLowerCase().trim()

  // 精确匹配
  if (lang === 'zh-cn' || lang === 'zh') {
    return 'zh-CN'
  }
  if (lang === 'en-us' || lang === 'en') {
    return 'en-US'
  }

  // 语言族匹配（匹配 zh-* 为中文，en-* 为英文）
  if (lang.startsWith('zh')) {
    return 'zh-CN'
  }
  if (lang.startsWith('en')) {
    return 'en-US'
  }

  return null
}

