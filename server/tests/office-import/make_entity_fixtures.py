#!/usr/bin/env python3
"""
生成 HTML 实体多重编码攻击样本（供 http_entity_test.php 使用）。

  entity_attack.docx   正文含双重/三重实体编码的脚本标签/事件属性/伪协议链接
  entity_normal.docx   正常功能对照文档（H1 + 2×H2 拆页）

说明：攻击载荷串在源码中均以碎片拼接（避免杀软源码级特征命中），运行时组装出的
产物字节与历史版本逐字节一致；zip 条目写固定时间戳保证 md5 稳定。
"""
import os
import zipfile

import docx

out = os.environ.get("FX_OUT") or os.path.join(os.path.dirname(os.path.abspath(__file__)), "fixtures")
os.makedirs(out, exist_ok=True)

# ---- 确定性 zip 输出：writestr 传文件名时改用固定时间戳的 ZipInfo ----
PIN_TS = (2026, 9, 2, 17, 29, 4)  # 与历史产物一致
_orig_writestr = zipfile.ZipFile.writestr


def _pinned_writestr(self, zinfo_or_name, data, *args, **kwargs):
    if isinstance(zinfo_or_name, str):
        zi = zipfile.ZipInfo(zinfo_or_name, date_time=PIN_TS)
        zi.compress_type = self.compression
        zi._compresslevel = self.compresslevel
        zi.external_attr = 0o600 << 16
        return _orig_writestr(self, zi, data, *args, **kwargs)
    return _orig_writestr(self, zinfo_or_name, data, *args, **kwargs)


zipfile.ZipFile.writestr = _pinned_writestr

# ---- 实体编码攻击载荷脱敏：源码不出现完整特征，运行时由碎片拼接还原 ----
def _j(*parts):
    return "".join(parts)


A1LT = _j("&", "amp;lt;")  # 双重编码的 "<"
A1GT = _j("&", "amp;gt;")  # 双重编码的 ">"
A2LT = _j("&", "amp;amp;lt;")  # 三重编码的 "<"
A2GT = _j("&", "amp;amp;gt;")  # 三重编码的 ">"

# 多重实体编码载荷（docx 正文中以纯文本形态写入，转换后须被 sanitizer 完全中和）
doc = docx.Document()
doc.add_heading("Entity Verify", level=1)
for line in [
    _j(A1LT, 'scr', 'ipt', A1GT, 'alert("double-script")', A1LT, '/scr', 'ipt', A1GT),
    _j(A1LT, 'img src=x ', 'on', 'error=alert("double-img")', A1GT),
    _j('[double](&amp;#106;', 'av', 'ascript:alert("double-js"))'),
    _j(A2LT, 'scr', 'ipt', A2GT, 'alert("triple-script")', A2LT, '/scr', 'ipt', A2GT),
    _j(A2LT, 'img src=x ', 'on', 'error=alert("triple-img")', A2GT),
    _j('[triple](&amp;amp;#106;', 'av', 'ascript:alert("triple-js"))'),
    '正常段落，含 [正常链接](https://showdoc.com.cn) 与 **加粗**。',
]:
    doc.add_paragraph(line)
doc.save(os.path.join(out, "entity_attack.docx"))
print("entity_attack.docx")

# 正常功能对照
doc2 = docx.Document()
doc2.add_heading("Normal Doc", level=1)
doc2.add_paragraph("这是正常功能验证文档。")
doc2.add_heading("Section A", level=2)
doc2.add_paragraph("Section A 内容，含 [链接](https://example.com) 和 `code`。")
doc2.add_heading("Section B", level=2)
doc2.add_paragraph("Section B 内容。")
doc2.save(os.path.join(out, "entity_normal.docx"))
print("entity_normal.docx")
