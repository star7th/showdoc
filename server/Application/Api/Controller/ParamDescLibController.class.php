<?php

namespace Api\Controller;

use Think\Controller;

class ParamDescLibController extends BaseController
{

  /**
   * 查询参数描述库条目
   */
  public function getEntries()
  {
    $this->_getEntries();
  }

  /**
   * 创建参数描述库条目
   */
  public function addEntry()
  {
    $this->_createEntry();
  }

  /**
   * 批量删除条目
   */
  public function deleteEntries()
  {
    $this->_deleteEntries();
  }

  /**
   * 删除单个条目
   */
  public function deleteEntry()
  {
    $this->_deleteEntry();
  }

  /**
   * 更新条目
   */
  public function updateEntry()
  {
    $this->_updateEntry();
  }

  /**
   * 添加临时条目
   */
  public function addTempEntry()
  {
    $this->temp();
  }

  /**
   * 临时条目转永久
   */
  public function promoteTemp()
  {
    $this->_promoteTemp();
  }

  /**
   * 确认导入
   */
  public function confirmImport()
  {
    $this->_confirmImport();
  }

  /**
   * 批量添加条目
   */
  public function addBatchEntries()
  {
    $this->_addBatchEntries();
  }

  /**
   * 更新使用次数
   */
  public function updateUsage()
  {
    $this->_updateUsage();
  }

  /**
   * 批量保存参数描述库条目（覆盖保存）
   */
  public function batchSave()
  {
    $this->_batchSave();
  }

  /**
   * 批量保存参数描述库条目（覆盖保存）
   */
  private function _batchSave()
  {
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];

    $item_id = I("itemId/d");
    $entries = I("entries", [], "", "");
    // 兼容前端以 JSON 字符串提交 entries 的情况
    if (is_string($entries)) {
      $decoded = json_decode($entries, true);
      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $entries = $decoded;
      }
    }

    if (!$item_id) {
      $this->sendError(10001, '缺少项目ID');
      return;
    }

    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }

    if (empty($entries)) {
      $this->sendError(10001, '没有要保存的条目');
      return;
    }

    try {
      // 1. 删除该项目下所有永久条目
      $deleteResult = D("ParameterDescriptionEntry")
        ->where(array(
          'item_id' => $item_id,
          'status' => 'permanent'
        ))
        ->delete();
      // 2. 批量创建新的永久条目
      $createdEntries = [];
      foreach ($entries as $entry) {
        // 容错：确保是数组
        if (!is_array($entry)) {
          continue;
        }
        // 兼容中文状态值，默认按永久处理
        $statusVal = isset($entry['status']) ? $entry['status'] : 'permanent';
        if ($statusVal === '永久') {
          $statusVal = 'permanent';
        }
        // 只保存永久条目，临时条目由前端内存管理
        if ($statusVal === 'permanent') {
          // 计算质量评分
          $qualityScore = $this->calculateQualityScore([
            'description' => $entry['description'],
            'example' => $entry['example'] ?? '',
            'enumValues' => $entry['enumValues'] ?? [],
            'defaultValue' => $entry['defaultValue'] ?? '',
            'usageCount' => $entry['usageCount'] ?? 0
          ]);

          $data = [
            'id' => $this->generateId(),
            'item_id' => $item_id,
            'name' => $entry['name'],
            'type' => $entry['type'],
            'description' => $entry['description'],
            'example' => $entry['example'] ?? '',
            'default_value' => $entry['defaultValue'] ?? '',
            'aliases' => json_encode($entry['aliases'] ?? []),
            'tags' => json_encode($entry['tags'] ?? []),
            'path' => $entry['path'] ?? '',
            'source' => $entry['source'] ?? 'manual',
            'status' => 'permanent',
            'usage_count' => $entry['usageCount'] ?? 0,
            'quality_score' => $qualityScore,
            'created_by' => $uid,
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
          ];

          if (!empty($entry['enumValues']) && is_array($entry['enumValues'])) {
            $data['enum_values'] = json_encode($entry['enumValues']);
          }

          $id = D("ParameterDescriptionEntry")->add($data);
          if ($id) {
            $createdEntry = D("ParameterDescriptionEntry")->where(array('id' => $data['id']))->find();
            $createdEntries[] = $this->convertEntryFields($createdEntry);
          }
        }
      }

      $this->sendResult($createdEntries);
    } catch (\Exception $e) {
      $this->sendError(10001, '批量保存失败: ' . $e->getMessage());
    }
  }

  /**
   * 获取参数描述库条目列表
   */
  private function _getEntries()
  {
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];

    $item_id = I("itemId/d");
    $keyword = I("keyword", "");
    $status = I("status", "");
    $name = I("name", "");
    $type = I("type", "");
    $tag = I("tag", "");
    $page = I("page/d", 1);
    $pageSize = I("pageSize/d", 20);

    if (!$item_id) {
      $this->sendError(10001, '缺少项目ID');
      return;
    }

    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }

    $where = "item_id = %d";
    $params = array($item_id);

    // 添加搜索条件
    if ($keyword) {
      $like = safe_like($keyword);
      $where .= " AND (name LIKE '%s' OR description LIKE '%s')";
      array_push($params, $like, $like);
    }

    if ($status) {
      $where .= " AND status = '%s'";
      $params[] = $status;
    }

    if ($name) {
      $where .= " AND name = '%s'";
      $params[] = $name;
    }

    if ($type) {
      $where .= " AND type = '%s'";
      $params[] = $type;
    }

    if ($tag) {
      $like_tag = '%"' . safe_like($tag) . '"%';
      $where .= " AND tags LIKE '%s'";
      $params[] = $like_tag;
    }

    // 计算总数
    $total = D("ParameterDescriptionEntry")->where($where, $params)->count();

    // 获取分页数据
    $offset = ($page - 1) * $pageSize;
    $entries = D("ParameterDescriptionEntry")
      ->where($where, $params)
      ->order("quality_score DESC, usage_count DESC, updated_at DESC")
      ->limit($offset, $pageSize)
      ->select();

    // 处理JSON字段并转换字段名为前端期望的驼峰格式
    foreach ($entries as &$entry) {
      $entry = $this->convertEntryFields($entry);
    }

    $this->sendResult([
      'data' => $entries,
      'total' => $total,
      'page' => $page,
      'pageSize' => $pageSize
    ]);
  }

  /**
   * 创建参数描述库条目
   */
  private function _createEntry()
  {
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];

    $item_id = I("itemId/d");
    $name = I("name", "");
    $type = I("type", "string");
    $description = I("description", "");
    $example = I("example", "");
    $enumValues = I("enumValues", []);
    $defaultValue = I("defaultValue", "");
    $aliases = I("aliases", []);
    $tags = I("tags", []);
    $path = I("path", "");
    $source = I("source", "manual");
    $status = I("status", "permanent");

    if (!$item_id || !$name || !$description) {
      $this->sendError(10001, '缺少必要参数');
      return;
    }

    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }

    // 检查是否已存在相同的条目
    $existing = D("ParameterDescriptionEntry")
      ->where(array(
        'item_id' => $item_id,
        'name' => $name,
        'type' => $type
      ))
      ->find();

    if ($existing) {
      $this->sendError(10001, '已存在相同的字段名和类型');
      return;
    }

    // 计算质量评分
    $qualityScore = $this->calculateQualityScore([
      'description' => $description,
      'example' => $example,
      'enumValues' => $enumValues,
      'defaultValue' => $defaultValue,
      'usageCount' => 0
    ]);

    $data = [
      'id' => $this->generateId(),
      'item_id' => $item_id,
      'name' => $name,
      'type' => $type,
      'description' => $description,
      'example' => $example,
      'default_value' => $defaultValue,
      'aliases' => json_encode($aliases),
      'tags' => json_encode($tags),
      'path' => $path,
      'source' => $source,
      'status' => $status,
      'usage_count' => 0,
      'quality_score' => $qualityScore,
      'created_by' => $uid,
      'created_at' => date("Y-m-d H:i:s"),
      'updated_at' => date("Y-m-d H:i:s")
    ];

    if (!empty($enumValues)) {
      $data['enum_values'] = json_encode($enumValues);
    }

    $id = D("ParameterDescriptionEntry")->add($data);

    if ($id) {
      // 返回创建的条目
      $entry = D("ParameterDescriptionEntry")->where(array('id' => $data['id']))->find();

      // 字段转换和处理
      $entry = $this->convertEntryFields($entry);

      $this->sendResult($entry);
    } else {
      $this->sendError(10001, '创建失败');
    }
  }

  /**
   * 批量删除条目
   */
  private function _deleteEntries()
  {
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];

    $ids = I("ids", "");

    // 处理前端传来的逗号分隔字符串
    if (is_string($ids)) {
      $ids = array_filter(explode(',', $ids));
    } elseif (!is_array($ids)) {
      $ids = [];
    }

    if (empty($ids)) {
      $this->sendError(10001, '缺少要删除的ID');
      return;
    }

    // 检查权限 - 获取第一个条目的item_id进行权限验证
    $firstEntry = D("ParameterDescriptionEntry")->where(array('id' => $ids[0]))->find();
    if (!$firstEntry) {
      $this->sendError(10001, '条目不存在');
      return;
    }

    if (!$this->checkItemEdit($uid, $firstEntry['item_id'])) {
      $this->sendError(10303);
      return;
    }

    // 构建删除条件
    $result = D("ParameterDescriptionEntry")->where(array('id' => array('in', $ids)))->delete();

    if ($result) {
      $this->sendResult(['deleted' => count($ids)]);
    } else {
      $this->sendError(10001, '删除失败');
    }
  }

  /**
   * 更新单个条目
   */
  private function _updateEntry()
  {
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];

    // 从POST参数中获取ID
    $id = I("id", "");
    if (!$id) {
      $this->sendError(10001, '缺少条目ID');
      return;
    }

    // 检查条目是否存在
    $entry = D("ParameterDescriptionEntry")->where(array('id' => $id))->find();
    if (!$entry) {
      $this->sendError(10001, '条目不存在');
      return;
    }

    if (!$this->checkItemEdit($uid, $entry['item_id'])) {
      $this->sendError(10303);
      return;
    }

    // 获取更新数据
    $updateData = [];

    $fields = ['name', 'type', 'description', 'example', 'defaultValue', 'path', 'source', 'status'];
    foreach ($fields as $field) {
      $value = I($field);
      if ($value !== null) {
        if ($field === 'defaultValue') {
          $updateData['default_value'] = $value;
        } else {
          $updateData[$field] = $value;
        }
      }
    }

    // 处理数组字段
    $aliases = I("aliases");
    if ($aliases !== null) {
      $updateData['aliases'] = json_encode($aliases);
    }

    $tags = I("tags");
    if ($tags !== null) {
      $updateData['tags'] = json_encode($tags);
    }

    $enumValues = I("enumValues");
    if ($enumValues !== null) {
      $updateData['enum_values'] = json_encode($enumValues);
    }

    if (!empty($updateData)) {
      $updateData['updated_at'] = date("Y-m-d H:i:s");

      // 重新计算质量评分
      if (isset($updateData['description']) || isset($updateData['example']) || isset($updateData['enum_values']) || isset($updateData['default_value'])) {
        $mergedData = array_merge($entry, $updateData);
        $mergedData['enumValues'] = isset($updateData['enum_values']) ? json_decode($updateData['enum_values'], true) : json_decode($entry['enum_values'], true);
        $updateData['quality_score'] = $this->calculateQualityScore($mergedData);
      }

      $result = D("ParameterDescriptionEntry")->where(array('id' => $id))->save($updateData);

      if ($result !== false) {
        // 返回更新后的条目
        $updatedEntry = D("ParameterDescriptionEntry")->where(array('id' => $id))->find();

        // 字段转换和处理
        $updatedEntry = $this->convertEntryFields($updatedEntry);

        $this->sendResult($updatedEntry);
      } else {
        $this->sendError(10001, '更新失败');
      }
    } else {
      $this->sendError(10001, '没有要更新的数据');
    }
  }

  /**
   * 删除单个条目
   */
  private function _deleteEntry()
  {
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];

    $id = I("id", "");
    if (!$id) {
      $this->sendError(10001, '缺少条目ID');
      return;
    }

    // 检查条目是否存在
    $entry = D("ParameterDescriptionEntry")->where(array('id' => $id))->find();
    if (!$entry) {
      $this->sendError(10001, '条目不存在');
      return;
    }

    if (!$this->checkItemEdit($uid, $entry['item_id'])) {
      $this->sendError(10303);
      return;
    }

    $result = D("ParameterDescriptionEntry")->where(array('id' => $id))->delete();

    if ($result) {
      $this->sendResult(['deleted' => 1]);
    } else {
      $this->sendError(10001, '删除失败');
    }
  }



  /**
   * 创建临时条目
   */
  public function temp()
  {
    $_POST['status'] = 'temp';
    $this->_createEntry();
  }

  /**
   * 临时条目转永久
   */
  private function _promoteTemp()
  {
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];

    $ids = I("ids", "");

    // 处理前端传来的逗号分隔字符串
    if (is_string($ids)) {
      $ids = array_filter(explode(',', $ids));
    } elseif (!is_array($ids)) {
      $ids = [];
    }

    if (empty($ids)) {
      $this->sendError(10001, '缺少要转换的ID');
      return;
    }

    // 检查权限
    $firstEntry = D("ParameterDescriptionEntry")->where(array('id' => $ids[0]))->find();
    if (!$firstEntry) {
      $this->sendError(10001, '条目不存在');
      return;
    }

    if (!$this->checkItemEdit($uid, $firstEntry['item_id'])) {
      $this->sendError(10303);
      return;
    }

    $result = D("ParameterDescriptionEntry")
      ->where(array('id' => array('in', $ids)))
      ->save(array(
        'status' => 'permanent',
        'updated_at' => date("Y-m-d H:i:s")
      ));

    if ($result !== false) {
      $this->sendResult(['promoted' => count($ids)]);
    } else {
      $this->sendError(10001, '转换失败');
    }
  }



  /**
   * 获取统计信息
   */
  public function stats()
  {
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];

    $item_id = I("itemId/d");

    if (!$item_id) {
      $this->sendError(10001, '缺少项目ID');
      return;
    }

    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }

    // 总数统计
    $totalCount = D("ParameterDescriptionEntry")->where(array('item_id' => $item_id))->count();
    $permanentCount = D("ParameterDescriptionEntry")->where(array('item_id' => $item_id, 'status' => 'permanent'))->count();
    $tempCount = D("ParameterDescriptionEntry")->where(array('item_id' => $item_id, 'status' => 'temp'))->count();

    // Top使用字段
    $topFields = D("ParameterDescriptionEntry")
      ->where(array('item_id' => $item_id))
      ->field("name, usage_count")
      ->order("usage_count DESC")
      ->limit(10)
      ->select();

    $topFieldsFormatted = [];
    foreach ($topFields as $field) {
      $topFieldsFormatted[] = [
        'name' => $field['name'],
        'count' => intval($field['usage_count'])
      ];
    }

    $this->sendResult([
      'totalCount' => $totalCount,
      'permanentCount' => $permanentCount,
      'tempCount' => $tempCount,
      'coverageRate' => $totalCount > 0 ? round($permanentCount / $totalCount * 100, 2) : 0,
      'hitRate' => 0, // 需要根据实际使用情况计算
      'topFields' => $topFieldsFormatted
    ]);
  }

  /**
   * 导入数据
   */
  public function import()
  {
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];

    $item_id = I("itemId/d");
    $format = I("format", "");
    $rawText = I("rawText", "");

    if (!$item_id || !$format || !$rawText) {
      $this->sendError(10001, '缺少必要参数');
      return;
    }

    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }

    $parsed = $this->parseImportData($format, $rawText);
    if (!$parsed) {
      $this->sendError(10001, '数据格式错误');
      return;
    }

    $newEntries = [];
    $duplicatedEntries = [];

    foreach ($parsed as $data) {
      // 检查是否已存在
      $existing = D("ParameterDescriptionEntry")
        ->where(array(
          'item_id' => $item_id,
          'name' => $data['name'],
          'type' => $data['type']
        ))
        ->find();

      $entry = [
        'id' => $this->generateId(),
        'itemId' => $item_id,
        'name' => $data['name'],
        'type' => $data['type'],
        'description' => $data['description'],
        'example' => isset($data['example']) ? $data['example'] : '',
        'source' => 'manual',
        'status' => 'permanent',
        'usageCount' => 0,
        'qualityScore' => $this->calculateQualityScore($data),
        'createdBy' => $uid,
        'updatedAt' => time()
      ];

      if ($existing) {
        $duplicatedEntries[] = $entry;
      } else {
        $newEntries[] = $entry;
      }
    }

    $this->sendResult([
      'new' => $newEntries,
      'duplicated' => $duplicatedEntries
    ]);
  }


  /**
   * 导出数据
   */
  public function export()
  {
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];

    $item_id = I("itemId/d");
    $format = I("format", "json");

    if (!$item_id) {
      $this->sendError(10001, '缺少项目ID');
      return;
    }

    if (!$this->checkItemEdit($uid, $item_id)) {
      $this->sendError(10303);
      return;
    }

    $entries = D("ParameterDescriptionEntry")
      ->where(array('item_id' => $item_id))
      ->order("name ASC")
      ->select();

    if ($format === 'csv') {
      $csv = "字段名,类型,描述,示例值,使用次数\n";
      foreach ($entries as $entry) {
        $csv .= "\"{$entry['name']}\",\"{$entry['type']}\",\"{$entry['description']}\",\"{$entry['example']}\",{$entry['usage_count']}\n";
      }
      $this->sendResult($csv);
    } else {
      // JSON格式
      $exportData = [];
      foreach ($entries as $entry) {
        $exportData[] = [
          'name' => $entry['name'],
          'type' => $entry['type'],
          'description' => $entry['description'],
          'example' => $entry['example'],
          'aliases' => json_decode($entry['aliases'], true),
          'tags' => json_decode($entry['tags'], true),
          'usageCount' => intval($entry['usage_count'])
        ];
      }
      $this->sendResult(json_encode($exportData, JSON_UNESCAPED_UNICODE));
    }
  }

  /**
   * 解析导入数据
   */
  private function parseImportData($format, $rawText)
  {
    switch ($format) {
      case 'kv':
        return $this->parseKeyValue($rawText);
      case 'json':
        return $this->parseJson($rawText);
      case 'ddl':
        return $this->parseDDL($rawText);
      default:
        return false;
    }
  }

  /**
   * 解析键值对格式
   */
  private function parseKeyValue($text)
  {
    $lines = explode("\n", trim($text));
    $result = [];

    foreach ($lines as $line) {
      $line = trim($line);
      if (empty($line)) continue;

      $parts = explode(':', $line, 2);
      if (count($parts) >= 2) {
        $result[] = [
          'name' => trim($parts[0]),
          'type' => 'string',
          'description' => trim($parts[1])
        ];
      }
    }

    return $result;
  }

  /**
   * 解析JSON格式
   */
  private function parseJson($text)
  {
    $data = json_decode($text, true);
    if (!$data) return false;

    return $this->extractFieldsFromObject($data);
  }

  /**
   * 从对象中提取字段
   */
  private function extractFieldsFromObject($obj, $prefix = '')
  {
    $result = [];

    foreach ($obj as $key => $value) {
      $fieldName = $prefix ? $prefix . '.' . $key : $key;

      if (is_array($value) && !empty($value)) {
        if (is_object($value[0]) || is_array($value[0])) {
          $result[] = [
            'name' => $fieldName,
            'type' => 'array',
            'description' => $fieldName . '数组'
          ];
          $result = array_merge($result, $this->extractFieldsFromObject($value[0], $fieldName . '[0]'));
        } else {
          $result[] = [
            'name' => $fieldName,
            'type' => 'array',
            'description' => $fieldName . '数组',
            'example' => json_encode($value)
          ];
        }
      } elseif (is_object($value) || is_array($value)) {
        $result[] = [
          'name' => $fieldName,
          'type' => 'object',
          'description' => $fieldName . '对象'
        ];
        $result = array_merge($result, $this->extractFieldsFromObject($value, $fieldName));
      } else {
        $type = 'string';
        if (is_int($value)) $type = 'number';
        elseif (is_bool($value)) $type = 'boolean';
        elseif (is_float($value)) $type = 'number';

        $result[] = [
          'name' => $fieldName,
          'type' => $type,
          'description' => $fieldName,
          'example' => strval($value)
        ];
      }
    }

    return $result;
  }

  /**
   * 解析DDL（简单实现）
   */
  private function parseDDL($text)
  {
    // 简单的DDL解析，提取字段名和注释
    $result = [];
    $lines = explode("\n", $text);

    foreach ($lines as $line) {
      $line = trim($line);
      if (preg_match('/`(\w+)`\s+\w+.*COMMENT\s+[\'"]([^\'"]+)[\'"]/', $line, $matches)) {
        $result[] = [
          'name' => $matches[1],
          'type' => 'string',
          'description' => $matches[2]
        ];
      }
    }

    return $result;
  }

  /**
   * 计算质量评分
   */
  private function calculateQualityScore($data)
  {
    $score = 0;

    // 基础分数
    if (!empty($data['description'])) $score += 0.4;
    if (!empty($data['example'])) $score += 0.2;
    if (!empty($data['enumValues']) && is_array($data['enumValues']) && count($data['enumValues']) > 0) $score += 0.1;
    if (!empty($data['defaultValue'])) $score += 0.1;

    // 使用频次加分
    $usageCount = isset($data['usageCount']) ? $data['usageCount'] : (isset($data['usage_count']) ? $data['usage_count'] : 0);
    $usageScore = min($usageCount * 0.02, 0.2);
    $score += $usageScore;

    return min($score, 1.0);
  }

  /**
   * 生成唯一ID
   */
  private function generateId()
  {
    return uniqid() . '_' . mt_rand(1000, 9999);
  }

  /**
   * 转换数据库字段名为前端驼峰格式
   */
  private function convertEntryFields($entry)
  {
    // 处理JSON字段
    $entry['aliases'] = $entry['aliases'] ? json_decode($entry['aliases'], true) : [];
    $entry['tags'] = $entry['tags'] ? json_decode($entry['tags'], true) : [];
    $entry['enumValues'] = $entry['enum_values'] ? json_decode($entry['enum_values'], true) : [];

    // 字段名转换：数据库下划线 -> 前端驼峰
    $entry['itemId'] = $entry['item_id'];
    $entry['usageCount'] = intval($entry['usage_count']);
    $entry['qualityScore'] = floatval($entry['quality_score']);
    $entry['defaultValue'] = $entry['default_value'] ?? '';
    $entry['createdBy'] = $entry['created_by'];
    $entry['updatedAt'] = strtotime($entry['updated_at']) * 1000; // 转换为前端时间戳格式

    // 移除数据库字段名
    unset($entry['item_id']);
    unset($entry['usage_count']);
    unset($entry['quality_score']);
    unset($entry['default_value']);
    unset($entry['enum_values']);
    unset($entry['created_by']);
    unset($entry['created_at']);
    unset($entry['updated_at']);

    return $entry;
  }

  /**
   * 批量添加条目
   */
  private function _addBatchEntries()
  {
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];

    $entries = I("entries", []);

    if (empty($entries)) {
      $this->sendError(10001, '没有要添加的条目');
      return;
    }

    $results = [];
    foreach ($entries as $entry) {
      // 检查权限
      if (!$this->checkItemEdit($uid, $entry['itemId'])) {
        continue;
      }

      $data = [
        'id' => $this->generateId(),
        'item_id' => $entry['itemId'],
        'name' => $entry['name'],
        'type' => $entry['type'],
        'description' => $entry['description'],
        'example' => $entry['example'] ?? '',
        'default_value' => $entry['defaultValue'] ?? '',
        'source' => $entry['source'] ?? 'manual',
        'status' => $entry['status'] ?? 'permanent',
        'usage_count' => 0,
        'quality_score' => $entry['qualityScore'] ?? 0,
        'created_by' => $uid,
        'created_at' => date("Y-m-d H:i:s"),
        'updated_at' => date("Y-m-d H:i:s")
      ];

      $id = D("ParameterDescriptionEntry")->add($data);
      if ($id) {
        $entry = D("ParameterDescriptionEntry")->where(array('id' => $data['id']))->find();
        $results[] = $this->convertEntryFields($entry);
      }
    }

    $this->sendResult($results);
  }

  /**
   * 确认导入
   */
  private function _confirmImport()
  {
    $this->_addBatchEntries();
  }

  /**
   * 更新使用次数
   */
  private function _updateUsage()
  {
    $login_user = $this->checkLogin();
    $uid = $login_user['uid'];

    $id = I("id", "");
    $ids = I("ids", "");

    if ($id) {
      $ids = [$id];
    } elseif ($ids) {
      // 处理前端传来的逗号分隔字符串
      if (is_string($ids)) {
        $ids = array_filter(explode(',', $ids));
      } elseif (!is_array($ids)) {
        $ids = [];
      }
    } else {
      $ids = [];
    }

    if (empty($ids)) {
      $this->sendError(10001, '缺少要更新的ID');
      return;
    }

    // 检查权限 - 检查第一个存在的条目来验证权限
    $firstValidEntry = null;
    foreach ($ids as $entryId) {
      $entry = D("ParameterDescriptionEntry")->where(array('id' => $entryId))->find();
      if ($entry) {
        $firstValidEntry = $entry;
        break;
      }
    }

    if (!$firstValidEntry) {
      $this->sendError(10001, '没有找到有效的条目');
      return;
    }

    if (!$this->checkItemEdit($uid, $firstValidEntry['item_id'])) {
      $this->sendError(10303);
      return;
    }

    $updated = 0;
    $notFound = [];

    foreach ($ids as $entryId) {
      $entry = D("ParameterDescriptionEntry")->where(array('id' => $entryId))->find();
      if ($entry) {
        // 验证该条目是否属于同一项目（安全检查）
        if ($entry['item_id'] != $firstValidEntry['item_id']) {
          continue; // 跳过不同项目的条目
        }

        $newUsageCount = $entry['usage_count'] + 1;
        $newQualityScore = $this->calculateQualityScore(array_merge($entry, ['usageCount' => $newUsageCount]));

        $result = D("ParameterDescriptionEntry")
          ->where(array('id' => $entryId))
          ->save(array(
            'usage_count' => $newUsageCount,
            'quality_score' => $newQualityScore,
            'updated_at' => date("Y-m-d H:i:s")
          ));

        if ($result !== false) {
          $updated++;
        }
      } else {
        $notFound[] = $entryId;
      }
    }

    $result = ['updated' => $updated];
    if (!empty($notFound)) {
      $result['notFound'] = $notFound;
    }

    $this->sendResult($result);
  }
}
