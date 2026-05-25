import DOMPurify from 'dompurify'

// 配置：允许常规 HTML 标签，禁止 iframe/video/script 等危险标签
DOMPurify.setConfig({
  ALLOWED_TAGS: [
    'p', 'span', 'a', 'img', 'b', 'i', 'em', 'strong', 'u', 's', 'del', 'ins',
    'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'table', 'thead', 'tbody', 'tr', 'th', 'td', 'caption',
    'blockquote', 'pre', 'code', 'br', 'hr', 'div', 'sub', 'sup',
    'details', 'summary', 'figure', 'figcaption', 'mark', 'small',
  ],
  ALLOWED_ATTR: ['href', 'src', 'alt', 'class', 'style', 'target', 'title', 'width', 'height', 'colspan', 'rowspan', 'id', 'rel'],
})

// 允许 a 标签的 target="_blank" 并自动加 rel="noopener noreferrer"
DOMPurify.addHook('afterSanitizeAttributes', (node) => {
  if (node.tagName === 'A' && node.getAttribute('target') === '_blank') {
    node.setAttribute('rel', 'noopener noreferrer')
  }
})

export function sanitizeHtml(html: string): string {
  return DOMPurify.sanitize(html)
}
