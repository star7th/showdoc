// 切换第 N 个任务项复选框状态（跳过代码块片段）
// 仅在非代码块片段中，用正则将第 N 次匹配的 - [ ] / - [x] 替换为目标状态
export function toggleNthTaskCheckbox(markdown, index, checked) {
  if (!markdown || index == null || index < 0) return markdown

  const codeBlockRegex = /```[ \t]*[^\n]*\n[\s\S]*?```/g
  const countRegex = /(\n|^)[ \t]*[-*+][ \t]+\[(?: |x|X)\]/g
  const replaceRegex = /(^[ \t]*[-*+][ \t]+\[)(?: |x|X)(\])/gm

  let parts = []
  let lastIndex = 0
  let m
  while ((m = codeBlockRegex.exec(markdown))) {
    if (m.index > lastIndex) {
      parts.push({ text: markdown.slice(lastIndex, m.index), isCode: false })
    }
    parts.push({ text: m[0], isCode: true })
    lastIndex = codeBlockRegex.lastIndex
  }
  if (lastIndex < markdown.length) {
    parts.push({ text: markdown.slice(lastIndex), isCode: false })
  }

  let targetGlobalIdx = index + 1 // 第 N 次匹配（index 从 0 开始）
  let seen = 0
  for (let i = 0; i < parts.length; i++) {
    const part = parts[i]
    if (part.isCode) continue
    const matches = part.text.match(countRegex)
    const partCount = matches ? matches.length : 0
    if (seen + partCount >= targetGlobalIdx) {
      const nthInPart = targetGlobalIdx - seen
      let count = 0
      part.text = part.text.replace(replaceRegex, (all, p1, p2) => {
        count += 1
        if (count === nthInPart) {
          return p1 + (checked ? 'x' : ' ') + p2
        }
        return all
      })
      break
    } else {
      seen += partCount
    }
  }

  return parts.map(p => p.text).join('')
}


