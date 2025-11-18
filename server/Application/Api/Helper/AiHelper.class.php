<?php

namespace Api\Helper;

/**
 * AI 辅助类
 * 提供 AI 相关的公共方法
 */
class AiHelper
{
  /**
   * 重建项目索引
   * @param int $item_id 项目ID
   * @param string $ai_service_url AI服务地址
   * @param string $ai_service_token AI服务Token
   * @return array|false 成功返回结果数组，失败返回false
   */
  public static function rebuild($item_id, $ai_service_url, $ai_service_token)
  {
    try {
      // 设置执行时长和内存限制（重建索引可能需要较长时间和较多内存）
      set_time_limit(600);  // 10分钟超时
      ini_set('memory_limit', '2G');  // 2GB 内存限制

      // 使用 ItemModel 的 getContent 方法获取所有页面
      // 注意：开源版没有分表机制，直接使用 getContent 即可
      $menu = D("Item")->getContent($item_id, "*", "*", 1);
      if (!$menu || (empty($menu['pages']) && empty($menu['catalogs']))) {
        \Think\Log::record("自动重建索引失败: item_id={$item_id}, 项目中没有可索引的文档");
        return false;
      }

      // 使用 Convert 类转换 API 文档
      $convert = new Convert();

      // 构建页面数据（递归处理所有页面）
      $pageData = array();
      self::_collectPages($menu, $pageData, $convert);

      if (empty($pageData)) {
        \Think\Log::record("自动重建索引失败: item_id={$item_id}, 没有可索引的文档（所有页面内容都为空）");
        return false;
      }

      \Think\Log::record("自动触发重建索引: item_id={$item_id}, 页面总数=" . count($pageData));

      // 调用 AI 服务重新索引（使用更长的超时时间）
      $url = rtrim($ai_service_url, '/') . '/api/index/rebuild';
      $postData = array(
        'item_id' => $item_id,
        'pages' => $pageData
      );

      $result = self::callService($url, $postData, $ai_service_token, 'POST', 600);  // 10分钟超时
      return $result;
    } catch (\Exception $e) {
      \Think\Log::record("自动重建索引异常: item_id={$item_id}, 错误: " . $e->getMessage());
      return false;
    }
  }

  /**
   * 调用 AI 服务（非流式）
   * @param string $url 请求URL
   * @param array $postData POST数据
   * @param string $ai_service_token AI服务Token
   * @param string $method 请求方法
   * @param int $timeout 超时时间（秒）
   * @return array|false
   */
  public static function callService($url, $postData = null, $ai_service_token, $method = 'POST', $timeout = 30)
  {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_ENCODING, '');

    $headers = array(
      'Content-Type: application/json; charset=utf-8',
      'Authorization: Bearer ' . $ai_service_token,
      'Accept: application/json; charset=utf-8'
    );

    if ($method == 'POST' && $postData) {
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_UNICODE));
    }

    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if ($result === false || $error) {
      $errorMsg = $error ? $error : '连接失败';
      \Think\Log::record("AI服务调用失败: " . $errorMsg . " (URL: " . $url . ")");
      return false;
    }

    if ($httpCode != 200) {
      \Think\Log::record("AI服务返回错误: HTTP " . $httpCode . ", Response: " . substr($result, 0, 500));
      return false;
    }

    // 确保响应是 UTF-8 编码
    if (!mb_check_encoding($result, 'UTF-8')) {
      $result = mb_convert_encoding($result, 'UTF-8', 'auto');
    }

    $data = json_decode($result, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
      \Think\Log::record("AI服务返回数据解析失败: " . json_last_error_msg() . ", Response: " . substr($result, 0, 500));
      return false;
    }

    return $data;
  }

  /**
   * 调用 AI 服务（流式）
   * @param string $url 请求URL
   * @param array $postData POST数据
   * @param string $ai_service_token AI服务Token
   */
  public static function callServiceStream($url, $postData, $ai_service_token)
  {
    $callback = function ($ch, $data) {
      if (connection_aborted()) {
        return -1;
      }

      // 确保数据是 UTF-8 编码
      if (!mb_check_encoding($data, 'UTF-8')) {
        $detected = mb_detect_encoding($data, array('UTF-8', 'GBK', 'GB2312', 'ISO-8859-1'), true);
        $data = mb_convert_encoding($data, 'UTF-8', $detected ? $detected : 'auto');
      }

      echo $data;
      flush();
      return strlen($data);
    };

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_UNICODE));
    curl_setopt($curl, CURLOPT_WRITEFUNCTION, $callback);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 300); // 5分钟超时
    curl_setopt($curl, CURLOPT_ENCODING, '');
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json; charset=utf-8',
      'Authorization: Bearer ' . $ai_service_token,
      'Accept: text/event-stream; charset=utf-8'
    ));

    curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    // 如果请求失败，输出错误信息
    if ($error || $httpCode != 200) {
      $errorMsg = $error ? $error : "HTTP {$httpCode}";
      echo "data: " . json_encode(array('type' => 'error', 'message' => 'AI 服务调用失败: ' . $errorMsg), JSON_UNESCAPED_UNICODE) . "\n\n";
      flush();
    }
  }

  /**
   * 递归收集所有页面数据
   * @param array $menu 菜单数据
   * @param array $pageData 页面数据数组（引用传递）
   * @param object $convert Convert 对象
   */
  private static function _collectPages($menu, &$pageData, $convert)
  {
    // 处理根目录下的页面
    if (isset($menu['pages']) && is_array($menu['pages'])) {
      foreach ($menu['pages'] as $page) {
        self::_processPage($page, $pageData, $convert);
      }
    }

    // 递归处理子目录
    if (isset($menu['catalogs']) && is_array($menu['catalogs'])) {
      foreach ($menu['catalogs'] as $catalog) {
        self::_collectPages($catalog, $pageData, $convert);
      }
    }
  }

  /**
   * 处理单个页面
   * @param array $page 页面数据
   * @param array $pageData 页面数据数组（引用传递）
   * @param object $convert Convert 对象
   */
  private static function _processPage($page, &$pageData, $convert)
  {
    $content = $page['page_content'];
    $pageType = isset($page['page_type']) ? $page['page_type'] : 'regular';

    // HTML 反转义（因为存储的内容是 HTML 转义的）
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // 尝试使用 Convert 类转换为 Markdown（如果是 API 文档会自动转换，否则返回 false）
    $md_content = $convert->runapiToMd($content);
    if ($md_content !== false) {
      $content = $md_content;
    }

    // 跳过空内容的页面
    if (empty($content) || !is_string($content) || trim($content) === '') {
      \Think\Log::record("跳过空内容页面: page_id={$page['page_id']}, title={$page['page_title']}");
      return;
    }

    $pageData[] = array(
      'page_id' => $page['page_id'],
      'page_title' => $page['page_title'],
      'page_content' => $content,
      'page_type' => $pageType,
      'cat_name' => isset($page['cat_name']) ? $page['cat_name'] : '',
      'update_time' => isset($page['update_time']) ? $page['update_time'] : time()
    );
  }
}

