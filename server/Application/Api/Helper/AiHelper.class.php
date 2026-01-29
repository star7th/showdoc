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
      set_time_limit(3600);  // 60分钟超时（大项目需要更长时间）
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

      $totalPages = count($pageData);

      // 重建索引前，先清空整个项目的旧索引，避免分批处理时重复删除操作
      $deleteUrl = rtrim($ai_service_url, '/') . '/api/index/delete-item';
      $deleteResult = self::callService($deleteUrl, array('item_id' => $item_id), $ai_service_token, 'DELETE', 30);
      if ($deleteResult === false) {
        \Think\Log::record("清空项目旧索引失败（可能不存在）: item_id={$item_id}");
      }

      // 分批处理，避免一次性发送所有页面数据导致超时或内存问题
      // 每批处理 200 个页面（可根据实际情况调整）
      $batchSize = 200;
      $url = rtrim($ai_service_url, '/') . '/api/index/rebuild';

      // 如果页面数量较少，一次性提交
      if ($totalPages <= 100) {
        $postData = array(
          'item_id' => $item_id,
          'pages' => $pageData
        );
        $result = self::callService($url, $postData, $ai_service_token, 'POST', 30);  // 30秒超时（只是提交任务）
        if ($result !== false && isset($result['status']) && $result['status'] == 'success') {
          \Think\Log::record("重建索引任务已提交: item_id={$item_id}, 页面总数={$totalPages}, task_id=" . (isset($result['task_id']) ? $result['task_id'] : ''));
          return array(
            'status' => 'success',
            'message' => '重建索引任务已提交，正在后台处理',
            'total' => $totalPages,
            'task_id' => isset($result['task_id']) ? $result['task_id'] : null
          );
        }
      }

      // 如果页面数量太多，分批提交（每批调用一次 rebuild 接口）
      $totalBatches = ceil($totalPages / $batchSize);
      $successBatches = 0;
      $errorBatches = 0;
      $taskIds = array();

      for ($i = 0; $i < $totalPages; $i += $batchSize) {
        $batch = array_slice($pageData, $i, $batchSize);
        $batchNum = floor($i / $batchSize) + 1;

        $postData = array(
          'item_id' => $item_id,
          'pages' => $batch
        );

        $result = self::callService($url, $postData, $ai_service_token, 'POST', 30);
        if ($result !== false && isset($result['status']) && $result['status'] == 'success') {
          $successBatches++;
          if (isset($result['task_id'])) {
            $taskIds[] = $result['task_id'];
          }
        } else {
          $errorBatches++;
          \Think\Log::record("重建索引进度: item_id={$item_id}, 批次 {$batchNum}/{$totalBatches} 提交失败");
        }

        // 每批之间稍作延迟，避免请求过于频繁
        if ($i + $batchSize < $totalPages) {
          usleep(200000);  // 延迟 0.2 秒
        }
      }

      return array(
        'status' => $errorBatches == 0 ? 'success' : 'partial_success',
        'message' => $errorBatches == 0 ? '重建索引任务已全部提交，正在后台处理' : "重建索引任务已提交，但有 {$errorBatches} 个批次失败",
        'total' => $totalPages,
        'total_batches' => $totalBatches,
        'success_batches' => $successBatches,
        'error_batches' => $errorBatches,
        'task_ids' => $taskIds
      );
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
    } elseif ($method == 'DELETE') {
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
      if ($postData) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_UNICODE));
      }
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
   * 异步调用 AI 服务（不等待响应）
   * @param string $url 请求URL
   * @param array $postData POST数据
   * @param string $ai_service_token AI服务Token
   * @param string $method 请求方法
   */
  public static function callAiServiceAsync($url, $postData = null, $ai_service_token, $method = 'POST')
  {
    try {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 增加到30秒超时，给AI服务足够的处理时间
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 连接超时5秒
      curl_setopt($ch, CURLOPT_NOSIGNAL, 1); // 避免信号量问题

      $headers = array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Bearer ' . $ai_service_token,
        'Accept: application/json; charset=utf-8'
      );

      if ($method == 'POST' && $postData) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_UNICODE));
      } elseif ($method == 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if ($postData) {
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData, JSON_UNESCAPED_UNICODE));
        }
      }

      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      // 执行请求并获取响应
      $result = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $error = curl_error($ch);
      curl_close($ch);

      // 只记录错误日志
      if ($error) {
        \Think\Log::record("AI服务调用失败: {$error}");
      } elseif ($httpCode != 200) {
        $responsePreview = $result ? substr($result, 0, 200) : '无响应';
        \Think\Log::record("AI服务返回错误: HTTP {$httpCode}, {$responsePreview}");
      }
    } catch (\Exception $e) {
      \Think\Log::record("AI服务调用异常: " . $e->getMessage());
    }
  }

  /**
   * 更新单个页面的索引（增量索引）
   * @param int $item_id 项目ID
   * @param int $page_id 页面ID
   * @param string $action 操作类型：'update' 或 'delete'
   * @param string $ai_service_url AI服务地址
   * @param string $ai_service_token AI服务Token
   * @return bool 成功返回 true，失败返回 false
   */
  public static function updatePageIndex($item_id, $page_id, $action, $ai_service_url, $ai_service_token)
  {
    try {
      // 设置超时时间（增量索引应该很快）
      set_time_limit(60);
      ini_set('memory_limit', '128M');

      $url = rtrim($ai_service_url, '/') . '/api/index/upsert';

      if ($action === 'delete') {
        // 删除页面索引（DELETE 方法）
        $postData = array(
          'item_id' => $item_id,
          'page_id' => $page_id
        );
        $result = self::callService($url, $postData, $ai_service_token, 'DELETE', 30);
      } else {
        // 更新页面索引，先获取页面内容
        $page = D("Page")->getById($page_id);
        if (!$page) {
          \Think\Log::record("获取页面失败: item_id={$item_id}, page_id={$page_id}");
          return false;
        }

        // 获取页面所属目录的名称，拼接到标题便于 AI 搜索（接口无 cat_name 参数）
        $catName = '';
        if (!empty($page['cat_id'])) {
          $catalog = D("Catalog")->getById($page['cat_id']);
          if ($catalog) {
            $catName = isset($catalog['cat_name']) ? $catalog['cat_name'] : '';
          }
        }
        $pageTitle = isset($page['page_title']) ? $page['page_title'] : '';
        if ($catName !== '') {
          $pageTitle = $catName . ' / ' . $pageTitle;
        }

        // 处理页面内容
        $content = isset($page['page_content']) ? $page['page_content'] : '';
        $pageType = isset($page['page_type']) ? $page['page_type'] : 'regular';

        // HTML 反转义
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 尝试转换为 Markdown（如果是 API 文档）
        $convert = new Convert();
        $mdContent = $convert->runapiToMd($content);
        if ($mdContent !== false) {
          $content = $mdContent;
        }

        // 跳过空内容
        if (empty($content) || trim($content) === '') {
          // 如果内容为空，则删除该页面的索引
          $postData = array(
            'item_id' => $item_id,
            'page_id' => $page_id
          );
          $result = self::callService($url, $postData, $ai_service_token, 'DELETE', 30);
        } else {
          $postData = array(
            'item_id' => $item_id,
            'page_id' => $page_id,
            'page_title' => $pageTitle,
            'page_content' => $content,
            'page_type' => $pageType,
            'update_time' => isset($page['update_time']) ? $page['update_time'] : time()
          );
          $result = self::callService($url, $postData, $ai_service_token, 'POST', 30);
        }
      }

      if ($result === false) {
        \Think\Log::record("更新页面索引失败: item_id={$item_id}, page_id={$page_id}, action={$action}");
        return false;
      }

      return true;
    } catch (Exception $e) {
      \Think\Log::record("更新页面索引异常: item_id={$item_id}, page_id={$page_id}, action={$action}, error=" . $e->getMessage());
      return false;
    }
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
    $pageId = isset($page['page_id']) ? $page['page_id'] : 0;
    $catId = isset($page['cat_id']) ? $page['cat_id'] : 0;

    // HTML 反转义（因为存储的内容是 HTML 转义的）
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // 尝试使用 Convert 类转换为 Markdown（如果是 API 文档会自动转换，否则返回 false）
    $md_content = $convert->runapiToMd($content);
    if ($md_content !== false) {
      $content = $md_content;
    }

    // 跳过空内容的页面
    if (empty($content) || !is_string($content) || trim($content) === '') {
      return;
    }

    // 获取页面所属目录的名称（主动查询），拼接到标题便于 AI 搜索（接口无 cat_name 参数）
    $catName = '';
    if ($catId > 0) {
      $catalog = D("Catalog")->getById($catId);
      if ($catalog) {
        $catName = isset($catalog['cat_name']) ? $catalog['cat_name'] : '';
      }
    }
    $pageTitle = isset($page['page_title']) ? $page['page_title'] : '';
    if ($catName !== '') {
      $pageTitle = $catName . ' / ' . $pageTitle;
    }

    $pageData[] = array(
      'page_id' => $pageId,
      'page_title' => $pageTitle,
      'page_content' => $content,
      'page_type' => $pageType,
      'update_time' => isset($page['update_time']) ? $page['update_time'] : time()
    );
  }
}

