<?php

namespace Api\Controller;

use Think\Controller;

class ExportHtmlController extends BaseController
{
  /**
   * 导出项目为离线HTML包
   */
  public function export()
  {
    set_time_limit(600);
    ini_set('memory_limit', '2G');

    $item_id = I("item_id/d");
    $login_user = $this->checkLogin();

    if (!$this->checkItemEdit($login_user['uid'], $item_id)) {
      $this->message(L('no_permissions'));
      return;
    }

    // 获取项目信息
    $item = D("Item")->where(array('item_id' => $item_id))->find();
    if (!$item) {
      $this->sendError(10101, L('item_not_exists'));
      return;
    }

    // 导出频率限制：同一用户当天最多 50 次（防刷）
    $todayStart = date('y-m-d 00:00:00');
    $timesToday = D("ExportLog")
      ->where(array('uid' => $login_user['uid'], 'export_type' => 'html'))
      ->where("addtime >= '{$todayStart}'")
      ->count();
    if ($timesToday >= 100) {
      $message = "为防止影响服务器负载，你当天导出次数已达上限(100次)。如有疑问请联系网站管理员";
      $this->sendError(10100, $message);
      return false;
    }

    // 检查项目类型
    $item_type = intval($item['item_type']);
    if (!in_array($item_type, [1, 3, 5])) {
      $this->sendError(10101, L('export_html_not_support_item_type'));
      return;
    }

    // 成员目录权限：获取该用户在此项目下允许的目录集合
    $allowedCatIds = D("Member")->getCatIds($item_id, $login_user['uid']);

    // 获取项目内容
    $menu = D("Item")->getContent($item_id, "*", "*", 1);

    // 应用目录权限过滤
    if (!empty($allowedCatIds)) {
      $allowed = array_flip(array_map('intval', $allowedCatIds));
      // 过滤根目录下的页面
      if (!empty($menu['pages'])) {
        $menu['pages'] = array();
      }
      // 过滤目录
      if (!empty($menu['catalogs'])) {
        $filteredCatalogs = array();
        foreach ($menu['catalogs'] as $cat) {
          if (isset($allowed[intval($cat['cat_id'])])) {
            $filteredCatalogs[] = $cat;
          }
        }
        $menu['catalogs'] = $filteredCatalogs;
      }
    }

    // 创建临时目录
    $temp_dir = sys_get_temp_dir() . "/showdoc_html_" . time() . "_" . rand(1000, 9999);
    if (!mkdir($temp_dir, 0755, true)) {
      $this->sendError(10101, L('create_temp_dir_failed'));
      return;
    }

    try {
      // 创建目录结构
      mkdir($temp_dir . '/pages', 0755, true);
      mkdir($temp_dir . '/assets/css', 0755, true);
      mkdir($temp_dir . '/assets/js', 0755, true);
      mkdir($temp_dir . '/assets/uploads', 0755, true);

      // 收集所有页面
      $all_pages = $this->_collectAllPages($menu);

      if (empty($all_pages)) {
        $this->sendError(10101, L('no_pages_to_export'));
        return;
      }

      // 生成页面HTML
      $this->_generatePagesHtml($all_pages, $item, $temp_dir);

      // 生成首页
      $this->_generateIndexHtml($item, $temp_dir);

      // 生成数据文件
      $this->_generateDataJs($menu, $item, $all_pages, $temp_dir);

      // 生成搜索索引
      $this->_generateSearchIndex($all_pages, $temp_dir);

      // 复制静态资源
      $this->_copyStaticFiles($temp_dir);

      // 复制图片和附件
      $this->_copyAssets($all_pages, $temp_dir);

      // 生成README
      $this->_generateReadme($item, $temp_dir);

      // 打包ZIP
      $zip_file = sys_get_temp_dir() . "/showdoc_html_" . $item_id . "_" . time() . ".zip";
      if (!$this->_zip($temp_dir, $zip_file)) {
        $this->sendError(10101, L('zip_failed'));
        return;
      }

      // 写导出记录（开源版已新增export_log表）
      D("ExportLog")->add(array(
        "uid" => $login_user['uid'],
        "export_type" => 'html',
        "item_id" => $item_id,
        "addtime" => date("y-m-d H:i:s")
      ));

      // 记录项目变更日志
      D("ItemChangeLog")->addLog($login_user['uid'], $item_id, 'export', 'item', $item_id, $item['item_name']);

      // 输出文件
      $filename = 'showdoc_offline_' . $this->_sanitizeFilename($item['item_name']) . '_' . date('YmdHis') . '.zip';
      
      // 处理中文文件名编码
      $encoded_filename = rawurlencode($filename);
      $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
      
      header("Cache-Control: max-age=0");
      header("Content-Description: File Transfer");
      
      // 根据浏览器类型设置文件名编码
      if (preg_match('/MSIE|Trident/i', $user_agent)) {
        // IE浏览器使用GBK编码
        $filename_gbk = mb_convert_encoding($filename, 'GBK', 'UTF-8');
        header('Content-disposition: attachment; filename="' . $filename_gbk . '"');
      } else {
        // 现代浏览器使用RFC 5987编码
        header('Content-disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . $encoded_filename);
      }
      
      header("Content-Type: application/zip");
      header("Content-Transfer-Encoding: binary");
      header('Content-Length: ' . filesize($zip_file));
      @readfile($zip_file);
      unlink($zip_file);
    } catch (\Exception $e) {
      error_log("Export HTML Error: " . $e->getMessage());
      $this->sendError(10101, L('export_failed') . ': ' . $e->getMessage());
    } finally {
      // 清理临时文件
      $this->_clearTempDir($temp_dir);
    }
  }

  /**
   * 收集所有页面（扁平化）
   */
  private function _collectAllPages($menu)
  {
    $pages = array();

    // 根目录下的页面
    if (!empty($menu['pages'])) {
      foreach ($menu['pages'] as $page) {
        $pages[] = $page;
      }
    }

    // 递归收集目录下的页面
    if (!empty($menu['catalogs'])) {
      $this->_collectPagesFromCatalogs($menu['catalogs'], $pages);
    }

    return $pages;
  }

  /**
   * 从目录中递归收集页面
   */
  private function _collectPagesFromCatalogs($catalogs, &$pages)
  {
    foreach ($catalogs as $cat) {
      if (!empty($cat['pages'])) {
        foreach ($cat['pages'] as $page) {
          $pages[] = $page;
        }
      }
      if (!empty($cat['catalogs'])) {
        $this->_collectPagesFromCatalogs($cat['catalogs'], $pages);
      }
    }
  }

  /**
   * 生成所有页面的HTML
   */
  private function _generatePagesHtml($pages, $item, $temp_dir)
  {
    import("Vendor.Parsedown.Parsedown");
    $Parsedown = new \Parsedown();
    $convert = new \Api\Helper\Convert();

    foreach ($pages as $page) {
      $page_id = intval($page['page_id']);
      $html = $this->_generatePageHtml($page, $item, $Parsedown, $convert);
      $file_path = $temp_dir . '/pages/page-' . $page_id . '.html';
      file_put_contents($file_path, $html);
    }
  }

  /**
   * 生成单个页面HTML
   */
  private function _generatePageHtml($page, $item, $Parsedown, $convert)
  {
    $page_id = intval($page['page_id']);
    $page_title = htmlspecialchars($page['page_title'], ENT_QUOTES, 'UTF-8');
    $item_name = htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8');

    // 处理内容
    $page_content = $page['page_content'];

    // RunAPI项目转换
    if ($item['item_type'] == '3') {
      $md_content = $convert->runapiToMd($page_content);
      if ($md_content) {
        $page_content = $md_content;
      }
    }

    // Markdown转HTML
    $html_content = $Parsedown->text($page_content);

    // 转义还原（数据库内容经过了html转义，需还原以正确显示）
    $html_content = htmlspecialchars_decode($html_content);

    // HTML安全过滤
    $html_content = $this->_sanitizeHtml($html_content);

    // 图片路径重写
    $html_content = $this->_rewriteImagePaths($html_content);

    // 生成HTML
    $html = '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $page_title . ' - ' . $item_name . '</title>
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/highlight.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 class="project-name">' . $item_name . '</h1>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="搜索页面...">
                <div id="searchResults" class="search-results"></div>
            </div>
            <button class="menu-toggle" id="menuToggle">☰</button>
        </div>
    </div>
    <div class="container">
        <aside class="sidebar" id="sidebar">
            <div id="catalogTree" class="catalog-tree"></div>
        </aside>
        <main class="main-content">
            <article class="page-content">
                <h1 class="page-title">' . $page_title . '</h1>
                <div class="markdown-body">' . $html_content . '</div>
                <div class="page-nav" id="pageNav"></div>
            </article>
        </main>
    </div>
    <script src="../assets/js/data.js"></script>
    <script src="../assets/js/search-index.js"></script>
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/highlight.min.js"></script>
    <script>
        if (typeof hljs !== "undefined") {
            hljs.highlightAll();
        }
        // 设置当前页面ID
        window.CURRENT_PAGE_ID = ' . $page_id . ';
    </script>
</body>
</html>';

    return $html;
  }

  /**
   * 生成首页HTML
   */
  private function _generateIndexHtml($item, $temp_dir)
  {
    $item_name = htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8');
    $item_description = htmlspecialchars($item['item_description'] ?? '', ENT_QUOTES, 'UTF-8');

    $html = '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $item_name . ' - 项目概览</title>
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/highlight.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 class="project-name">' . $item_name . '</h1>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="搜索页面...">
                <div id="searchResults" class="search-results"></div>
            </div>
            <button class="menu-toggle" id="menuToggle">☰</button>
        </div>
    </div>
    <div class="container">
        <aside class="sidebar" id="sidebar">
            <div id="catalogTree" class="catalog-tree"></div>
        </aside>
        <main class="main-content">
            <article class="page-content">
                <h1 class="page-title">' . $item_name . '</h1>
                ' . ($item_description ? '<p class="project-description">' . $item_description . '</p>' : '') . '
                <div class="project-overview">
                    <p>这是一个离线HTML文档包，您可以在浏览器中离线浏览所有文档。</p>
                    <p>使用左侧目录树导航，或使用顶部搜索框快速查找内容。</p>
                </div>
            </article>
        </main>
    </div>
    <script src="assets/js/data.js"></script>
    <script src="assets/js/search-index.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        window.CURRENT_PAGE_ID = 0;
    </script>
</body>
</html>';

    file_put_contents($temp_dir . '/index.html', $html);
  }

  /**
   * 生成data.js文件
   */
  private function _generateDataJs($menu, $item, $all_pages, $temp_dir)
  {
    // 收集所有目录（扁平化）
    $all_catalogs = $this->_collectAllCatalogs($menu);

    // 构建页面列表
    $pages_list = array();
    foreach ($all_pages as $page) {
      $pages_list[] = array(
        'page_id' => intval($page['page_id']),
        'page_title' => $page['page_title'],
        'cat_id' => intval($page['cat_id'] ?? 0),
        's_number' => intval($page['s_number'] ?? 0),
        'file_path' => 'pages/page-' . intval($page['page_id']) . '.html'
      );
    }

    // 构建目录列表
    $catalogs_list = array();
    foreach ($all_catalogs as $cat) {
      $catalogs_list[] = array(
        'cat_id' => intval($cat['cat_id']),
        'cat_name' => $cat['cat_name'],
        'parent_cat_id' => intval($cat['parent_cat_id'] ?? 0),
        'level' => intval($cat['level'] ?? 2),
        's_number' => intval($cat['s_number'] ?? 0)
      );
    }

    $data = array(
      'item_id' => intval($item['item_id']),
      'item_name' => $item['item_name'],
      'item_type' => $item['item_type'],
      'item_description' => $item['item_description'] ?? '',
      'catalogs' => $catalogs_list,
      'pages' => $pages_list
    );

    $js_content = 'window.PROJECT_DATA = ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . ';';
    file_put_contents($temp_dir . '/assets/js/data.js', $js_content);
  }

  /**
   * 收集所有目录（扁平化）
   */
  private function _collectAllCatalogs($menu)
  {
    $catalogs = array();

    if (!empty($menu['catalogs'])) {
      $this->_collectCatalogsRecursive($menu['catalogs'], $catalogs);
    }

    return $catalogs;
  }

  /**
   * 递归收集目录
   */
  private function _collectCatalogsRecursive($catalogs_list, &$result)
  {
    foreach ($catalogs_list as $cat) {
      $result[] = array(
        'cat_id' => $cat['cat_id'],
        'cat_name' => $cat['cat_name'],
        'parent_cat_id' => $cat['parent_cat_id'] ?? 0,
        'level' => $cat['level'] ?? 2,
        's_number' => $cat['s_number'] ?? 0
      );
      if (!empty($cat['catalogs'])) {
        $this->_collectCatalogsRecursive($cat['catalogs'], $result);
      }
    }
  }

  /**
   * 生成搜索索引
   */
  private function _generateSearchIndex($pages, $temp_dir)
  {
    $index = array();

    foreach ($pages as $page) {
      // 先还原转义再提取文本
      $decoded = htmlspecialchars_decode($page['page_content']);
      $content = strip_tags($decoded);
      $content_preview = mb_substr($content, 0, 200);

      $index[] = array(
        'page_id' => intval($page['page_id']),
        'page_title' => $page['page_title'],
        'content_preview' => $content_preview,
        'cat_id' => intval($page['cat_id'] ?? 0)
      );
    }

    $js_content = 'window.SEARCH_INDEX = ' . json_encode($index, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . ';';
    file_put_contents($temp_dir . '/assets/js/search-index.js', $js_content);
  }

  /**
   * 复制静态文件（CSS/JS）
   */
  private function _copyStaticFiles($temp_dir)
  {
    $base_path = dirname(dirname(dirname(dirname(__DIR__))));

    // 复制highlight.js
    $highlight_js = $base_path . '/Public/highlight/highlight.min.js';
    if (file_exists($highlight_js)) {
      copy($highlight_js, $temp_dir . '/assets/js/highlight.min.js');
    }

    // 复制highlight.css
    $highlight_css = $base_path . '/Public/highlight/default.min.css';
    if (file_exists($highlight_css)) {
      copy($highlight_css, $temp_dir . '/assets/css/highlight.css');
    }

    // 生成common.css和app.js
    $this->_generateCommonCss($temp_dir);
    $this->_generateAppJs($temp_dir);
  }

  /**
   * 生成common.css
   */
  private function _generateCommonCss($temp_dir)
  {
    $base_path = dirname(dirname(dirname(dirname(__DIR__))));
    $css_file = $base_path . '/server/Application/Static/export-html/common.css';
    if (file_exists($css_file)) {
      $css = file_get_contents($css_file);
      file_put_contents($temp_dir . '/assets/css/common.css', $css);
    }
  }

  /**
   * 生成app.js
   */
  private function _generateAppJs($temp_dir)
  {
    $base_path = dirname(dirname(dirname(dirname(__DIR__))));
    $js_file = $base_path . '/server/Application/Static/export-html/app.js';
    if (file_exists($js_file)) {
      $js = file_get_contents($js_file);
      file_put_contents($temp_dir . '/assets/js/app.js', $js);
    }
  }

  /**
   * 复制图片和附件
   */
  private function _copyAssets($pages, $temp_dir)
  {
    $base_path = dirname(dirname(dirname(dirname(__DIR__))));
    $uploads_dir = $base_path . '/Public/Uploads';
    $target_dir = $temp_dir . '/assets/uploads';

    if (!is_dir($uploads_dir)) {
      return;
    }

    // 从页面内容中提取图片路径
    $image_paths = array();
    foreach ($pages as $page) {
      $content = $page['page_content'];
      // 匹配 /Public/Uploads/xxx 格式的路径
      preg_match_all('/\/Public\/Uploads\/([^\s"\'\)]+)/', $content, $matches);
      if (!empty($matches[1])) {
        foreach ($matches[1] as $img_path) {
          $image_paths[$img_path] = true;
        }
      }
    }

    // 复制图片文件
    foreach (array_keys($image_paths) as $img_path) {
      $source = $uploads_dir . '/' . $img_path;
      if (file_exists($source) && is_file($source)) {
        $target = $target_dir . '/' . basename($img_path);
        // 确保目录存在
        $target_dir_path = dirname($target);
        if (!is_dir($target_dir_path)) {
          mkdir($target_dir_path, 0755, true);
        }
        copy($source, $target);
      }
    }
  }

  /**
   * 生成README文件
   */
  private function _generateReadme($item, $temp_dir)
  {
    $item_name = htmlspecialchars($item['item_name'], ENT_QUOTES, 'UTF-8');
    $readme = "ShowDoc 离线HTML文档包
====================

项目名称: {$item_name}
导出时间: " . date('Y-m-d H:i:s') . "

使用说明:
---------
1. 解压此压缩包到任意目录
2. 用浏览器打开 index.html 文件
3. 使用左侧目录树导航，或使用顶部搜索框查找内容
4. 所有资源已包含在压缩包中，可完全离线使用

注意事项:
---------
- 此文档包为只读版本，无法编辑
- 建议使用现代浏览器（Chrome、Firefox、Edge、Safari）
- 如需更新文档，请重新导出

";
    file_put_contents($temp_dir . '/README.txt', $readme);
  }

  /**
   * HTML安全过滤
   */
  private function _sanitizeHtml($html)
  {
    // 移除script标签
    $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);

    // 移除事件处理器
    $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
    $html = preg_replace('/\s*on\w+\s*=\s*[^\s>]+/i', '', $html);

    // 过滤危险协议
    $html = preg_replace('/href\s*=\s*["\'](javascript|data):/i', 'href="#"', $html);
    $html = preg_replace('/src\s*=\s*["\'](javascript|data):/i', 'src="#"', $html);

    return $html;
  }

  /**
   * 重写图片路径
   */
  private function _rewriteImagePaths($html)
  {
    // 处理图片路径：/Public/Uploads/xxx 转换为相对路径
    $html = preg_replace_callback(
      '/src\s*=\s*["\']([^"\']*\/Public\/Uploads\/[^"\']+)["\']/i',
      function ($matches) {
        $path = $matches[1];
        $filename = basename($path);
        return 'src="../assets/uploads/' . $filename . '"';
      },
      $html
    );

    return $html;
  }

  /**
   * 清理文件名
   */
  private function _sanitizeFilename($filename)
  {
    $filename = trim($filename);
    $filename = preg_replace('/[<>:"\/\\\|\?\*\x00-\x1F]/', '_', $filename);
    $filename = preg_replace('/[_\.]+/', '_', $filename);
    $filename = trim($filename, '_.');
    if (empty($filename)) {
      $filename = 'unnamed';
    }
    if (mb_strlen($filename) > 200) {
      $filename = mb_substr($filename, 0, 200);
    }
    return $filename;
  }

  /**
   * 打包ZIP
   */
  private function _zip($fromName, $toName)
  {
    if (!file_exists($fromName) || !is_dir($fromName)) {
      return false;
    }

    $zipArc = new \ZipArchive();
    if (!$zipArc->open($toName, \ZipArchive::CREATE)) {
      return false;
    }

    $this->_addDirectoryToZip($fromName, $zipArc, '');
    return $zipArc->close();
  }

  /**
   * 递归添加目录到ZIP
   */
  private function _addDirectoryToZip($dir, $zipArc, $zipPath)
  {
    $files = scandir($dir);
    foreach ($files as $file) {
      if ($file == '.' || $file == '..') {
        continue;
      }

      $filePath = $dir . '/' . $file;
      $zipFilePath = $zipPath ? $zipPath . '/' . $file : $file;

      if (is_dir($filePath)) {
        $this->_addDirectoryToZip($filePath, $zipArc, $zipFilePath);
      } else {
        $zipArc->addFile($filePath, $zipFilePath);
      }
    }
  }

  /**
   * 清理临时目录
   */
  private function _clearTempDir($dir)
  {
    if (!is_dir($dir)) {
      return;
    }

    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
      $path = $dir . '/' . $file;
      if (is_dir($path)) {
        $this->_clearTempDir($path);
      } else {
        unlink($path);
      }
    }
    rmdir($dir);
  }
}

