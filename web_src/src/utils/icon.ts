/**
 * FontAwesome 图标工具函数
 * 用于处理图标数组和 class 字符串的转换
 */

/**
 * 转换图标配置为 CSS class 字符串
 *
 * @param icon - 图标配置,可以是字符串或数组
 *   - 字符串:直接返回,如 "fas fa-star"
 *   - 数组:["fas", "star"] 转换为 "fas fa-star"
 *   - 数组:["fas", "fa-star"] 检测已有 fa- 前缀,智能处理
 *
 * @returns CSS class 字符串
 *
 * @example
 * getIconClass("fas fa-star")            // "fas fa-star"
 * getIconClass(["fas", "star"])          // "fas fa-star"
 * getIconClass(["fas", "fa-star"])     // "fas fa-star" (检测到 fa- 前缀)
 */
export function getIconClass(icon: string | string[]): string {
  // 如果是字符串,直接返回
  if (typeof icon === 'string') {
    return icon;
  }

  // 如果是数组,转换
  if (Array.isArray(icon)) {
    const [style, iconName] = icon;

    // 如果图标名称已经包含 fa- 前缀,就直接使用(如 ["fas", "fa-star"])
    if (iconName.startsWith('fa-')) {
      return `${style} ${iconName}`;
    }

    // 否则,添加 fa- 前缀(如 ["fas", "star"])
    return `${style} fa-${iconName}`;
  }

  // 其他情况,返回空字符串
  return '';
}
