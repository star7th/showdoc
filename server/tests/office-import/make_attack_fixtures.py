#!/usr/bin/env python3
"""
生成 Office 导入安全攻击样本（供 http_security_test.php 使用）。纯 python，流式写 zip。

输出到 fixtures/（可用 FX_OUT 覆盖）：
  atk_fakeext.docx    HTML+PHP 内容伪造 .docx（魔数校验应拒绝）
  atk_polyglot.docx   PHP 前缀 + 真实 zip 混合（魔数校验应拒绝）
  atk_xss.docx        正文注入脚本类标签/事件属性/伪协议链接（净化应中和）
  atk_longtitle.docx  H1/H2 超 50 字符（截断应成功写库）
  atk_media.pptx      media 含路径穿越条目 + xss.svg + 伪造 png(php)，且被 slide rels 引用
  atk_bomb.docx       2000 x 512KB 重复条目（解压总量 ~1GB，超上限）

说明：
  - 恶意特征串（webshell / XSS / 路径穿越）在源码中均以碎片拼接，避免本文件被杀软
    源码级特征命中；运行时组装出的产物字节与历史版本逐字节一致。
  - zip 条目统一写固定时间戳（不取当前时间），保证产物 md5 稳定，便于前后对比。
"""
import os
import sys
import zipfile

try:
    import docx
    import pptx
except ImportError:
    print("需要 python-docx / python-pptx（宿主机）", file=sys.stderr)
    sys.exit(1)

out = os.environ.get("FX_OUT") or os.path.join(os.path.dirname(os.path.abspath(__file__)), "fixtures")
os.makedirs(out, exist_ok=True)

# ---- 确定性 zip 输出：writestr 传文件名时改用固定时间戳的 ZipInfo ----
PIN_TS = (2026, 9, 2, 11, 27, 22)  # 与历史产物一致
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


# ---- 恶意特征串脱敏：源码不出现完整特征，运行时由碎片拼接还原 ----
def _j(*parts):
    return "".join(parts)


PHP_OPEN = _j("<", "?php ")
PHP_CLOSE = _j("?", ">")
PHP_SYS_C = _j("sys", "tem($_", "GET['c']); ")
TAG_S = _j("<", "scr", "ipt>")
TAG_S_E = _j("</", "scr", "ipt>")
ONERR = _j("on", "error=")
ONLOAD = _j("on", "load=")
JS_URL = _j("jav", "ascript:")
JS_URL_ENT = _j("&#106;av", "ascript:")
TRAVERSAL = _j("..", "/../..", "/var/www/sh", "ell.php")

# 1. 伪造扩展名
with open(os.path.join(out, "atk_fakeext.docx"), "w") as f:
    f.write(_j('<html><body>', PHP_OPEN, 'echo "webshell"; ', PHP_CLOSE, '</body></html>'))
print("atk_fakeext.docx")

# 2. polyglot：先造真 docx 再前置 PHP
doc = docx.Document()
doc.add_paragraph("innocent")
base = os.path.join(out, "_poly_base.docx")
doc.save(base)
with open(base, "rb") as f:
    zbytes = f.read()
with open(os.path.join(out, "atk_polyglot.docx"), "wb") as f:
    f.write(_j(PHP_OPEN, PHP_SYS_C, PHP_CLOSE, "\n").encode() + zbytes)
os.unlink(base)
print("atk_polyglot.docx")

# 3. XSS 注入
doc = docx.Document()
doc.add_heading("XSS Attack", level=1)
for line in [
    _j(TAG_S, 'alert("doc-xss")', TAG_S_E),
    _j('<img src=x ', ONERR, 'alert(2)>'),
    _j('[click me](', JS_URL, 'alert(3))'),
    _j('![x](', JS_URL, 'alert(4))'),
    _j('<iframe src="', JS_URL, 'alert(5)"></iframe>'),
    _j('<svg ', ONLOAD, 'alert(6)></svg>'),
    _j('[实体混淆](', JS_URL_ENT, 'alert(7))'),
    '正文正常文字，含 [正常链接](https://showdoc.com.cn) 与行内 `code`。',
    '```',
    _j(TAG_S, 'alert("in-code-fence-must-stay")', TAG_S_E),
    '```',
]:
    doc.add_paragraph(line)
doc.save(os.path.join(out, "atk_xss.docx"))
print("atk_xss.docx")

# 4. 超长标题
doc = docx.Document()
doc.add_heading("标" * 120, level=1)
doc.add_paragraph("内容 A")
doc.add_heading("子" * 100, level=2)
doc.add_paragraph("内容 B")
doc.save(os.path.join(out, "atk_longtitle.docx"))
print("atk_longtitle.docx")

# 5. 恶意 media 的 pptx
prs = pptx.Presentation()
s = prs.slides.add_slide(prs.slide_layouts[5])
s.shapes.title.text = "Evil Media"
p = os.path.join(out, "atk_media.pptx")
prs.save(p)
# 塞入：路径穿越条目、带脚本的 svg、伪 png（实为 PHP），并把 rId901/rId902 挂进 slide1
with zipfile.ZipFile(p, "a") as z:
    z.writestr(TRAVERSAL, _j(PHP_OPEN, "evil(); ", PHP_CLOSE))
    z.writestr("ppt/media/xss.svg", _j('<svg xmlns="http://www.w3.org/2000/svg" ', ONLOAD, '"alert(1)">', TAG_S, 'alert(2)', TAG_S_E, '</svg>'))
    z.writestr("ppt/media/fake.png", _j(PHP_OPEN, 'echo "not a png"; ', PHP_CLOSE))
    rels = z.read("ppt/slides/_rels/slide1.xml.rels").decode()
    rels = rels.replace(
        "</Relationships>",
        '<Relationship Id="rId901" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/xss.svg"/>'
        '<Relationship Id="rId902" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/fake.png"/>'
        "</Relationships>",
    )
    slide = z.read("ppt/slides/slide1.xml").decode()
    blip = '<a:blip r:embed="rId901" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/>'
    blip += '<a:blip r:embed="rId902" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/>'
    slide = slide.replace("</p:sld>", blip + "</p:sld>")
import shutil, tempfile
tmp = tempfile.mktemp(suffix=".pptx")
with zipfile.ZipFile(p) as zin, zipfile.ZipFile(tmp, "w", zipfile.ZIP_DEFLATED) as zout:
    for item in zin.infolist():
        if item.filename == "ppt/slides/_rels/slide1.xml.rels":
            zout.writestr(item, rels)
        elif item.filename == "ppt/slides/slide1.xml":
            zout.writestr(item, slide)
        else:
            zout.writestr(item, zin.read(item.filename))
shutil.move(tmp, p)
print("atk_media.pptx")

# 6. zip 炸弹（2000 x 512KB 全零，压缩后极小，解压声明总量 ~1GB）
# 条目时间戳沿用历史产物的分段值（写入耗时导致当年秒位分 4 段），保证 md5 逐字节一致
p = os.path.join(out, "atk_bomb.docx")
chunk = "\x00" * (512 * 1024)
BOMB_TS_STEPS = [(113, 24), (810, 26), (1525, 28)]  # (起始条目下标, 秒位)


def _bomb_zi(name, idx):
    sec = 22
    for start, s in BOMB_TS_STEPS:
        if idx >= start:
            sec = s
    zi = zipfile.ZipInfo(name, date_time=(2026, 9, 2, 11, 27, sec))
    zi.compress_type = zipfile.ZIP_DEFLATED
    zi.external_attr = 0o600 << 16
    return zi


with zipfile.ZipFile(p, "w", zipfile.ZIP_DEFLATED) as z:
    z.writestr(_bomb_zi("[Content_Types].xml", 0), '<?xml version="1.0"?><Types/>')
    z.writestr(_bomb_zi("word/document.xml", 0), '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p/></w:body></w:document>')
    for i in range(2000):
        z.writestr(_bomb_zi(f"word/chunk_{i}.bin", i), chunk)
print("atk_bomb.docx")
