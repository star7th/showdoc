<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Model\ParameterDescriptionEntry;
use App\Common\Helper\Security;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class ParamDescLibController extends BaseController
{
    /**
     * 查询参数描述库条目
     */
    public function getEntries(Request $request, Response $response): Response
    {
        return $this->_getEntries($request, $response);
    }

    /**
     * 创建参数描述库条目
     */
    public function addEntry(Request $request, Response $response): Response
    {
        return $this->_createEntry($request, $response);
    }

    /**
     * 批量删除条目
     */
    public function deleteEntries(Request $request, Response $response): Response
    {
        return $this->_deleteEntries($request, $response);
    }

    /**
     * 删除单个条目
     */
    public function deleteEntry(Request $request, Response $response): Response
    {
        return $this->_deleteEntry($request, $response);
    }

    /**
     * 更新条目
     */
    public function updateEntry(Request $request, Response $response): Response
    {
        return $this->_updateEntry($request, $response);
    }

    /**
     * 添加临时条目
     */
    public function addTempEntry(Request $request, Response $response): Response
    {
        return $this->temp($request, $response);
    }

    /**
     * 临时条目转永久
     */
    public function promoteTemp(Request $request, Response $response): Response
    {
        return $this->_promoteTemp($request, $response);
    }

    /**
     * 确认导入
     */
    public function confirmImport(Request $request, Response $response): Response
    {
        return $this->_confirmImport($request, $response);
    }

    /**
     * 批量添加条目
     */
    public function addBatchEntries(Request $request, Response $response): Response
    {
        return $this->_addBatchEntries($request, $response);
    }

    /**
     * 更新使用次数
     */
    public function updateUsage(Request $request, Response $response): Response
    {
        return $this->_updateUsage($request, $response);
    }

    /**
     * 批量保存参数描述库条目（覆盖保存）
     */
    public function batchSave(Request $request, Response $response): Response
    {
        return $this->_batchSave($request, $response);
    }

    /**
     * 获取统计信息
     */
    public function stats(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'itemId', 0);

        if (!$itemId) {
            return $this->error($response, 10001, '缺少项目ID');
        }

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        // 总数统计
        $totalCount = ParameterDescriptionEntry::count(['item_id' => $itemId]);
        $permanentCount = ParameterDescriptionEntry::count(['item_id' => $itemId, 'status' => 'permanent']);
        $tempCount = ParameterDescriptionEntry::count(['item_id' => $itemId, 'status' => 'temp']);

        // Top使用字段
        $topFields = ParameterDescriptionEntry::getTopFields($itemId, 10);

        $topFieldsFormatted = [];
        foreach ($topFields as $field) {
            $topFieldsFormatted[] = [
                'name' => $field->name,
                'count' => (int) $field->usage_count
            ];
        }

        return $this->success($response, [
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
    public function import(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'itemId', 0);
        $format = $this->getParam($request, 'format', '');
        $rawText = $this->getParam($request, 'rawText', '');

        if (!$itemId || !$format || !$rawText) {
            return $this->error($response, 10001, '缺少必要参数');
        }

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        $parsed = $this->parseImportData($format, $rawText);
        if (!$parsed) {
            return $this->error($response, 10001, '数据格式错误');
        }

        $newEntries = [];
        $duplicatedEntries = [];

        foreach ($parsed as $data) {
            // 检查是否已存在
            $existing = ParameterDescriptionEntry::findExisting($itemId, $data['name'], $data['type']);

            $entry = [
                'id' => $this->generateId(),
                'itemId' => $itemId,
                'name' => $data['name'],
                'type' => $data['type'],
                'description' => $data['description'],
                'example' => $data['example'] ?? '',
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

        return $this->success($response, [
            'new' => $newEntries,
            'duplicated' => $duplicatedEntries
        ]);
    }

    /**
     * 导出数据
     */
    public function export(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'itemId', 0);
        $format = $this->getParam($request, 'format', 'json');

        if (!$itemId) {
            return $this->error($response, 10001, '缺少项目ID');
        }

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        $result = ParameterDescriptionEntry::getList(['item_id' => $itemId], 1, 10000, 'name', 'ASC');
        $entries = $result['data'];

        if ($format === 'csv') {
            $csv = "字段名,类型,描述,示例值,使用次数\n";
            foreach ($entries as $entry) {
                $entry = (array) $entry;
                $csv .= "\"{$entry['name']}\",\"{$entry['type']}\",\"{$entry['description']}\",\"{$entry['example']}\",{$entry['usage_count']}\n";
            }
            return $this->success($response, $csv);
        } else {
            // JSON格式
            $exportData = [];
            foreach ($entries as $entry) {
                $entry = (array) $entry;
                $exportData[] = [
                    'name' => $entry['name'],
                    'type' => $entry['type'],
                    'description' => $entry['description'],
                    'example' => $entry['example'],
                    'aliases' => json_decode($entry['aliases'] ?? '[]', true),
                    'tags' => json_decode($entry['tags'] ?? '[]', true),
                    'usageCount' => (int) ($entry['usage_count'] ?? 0)
                ];
            }
            return $this->success($response, json_encode($exportData, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 批量保存参数描述库条目（覆盖保存）
     */
    private function _batchSave(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'itemId', 0);
        $entries = $this->getParam($request, 'entries');

        // 兼容前端以 JSON 字符串提交 entries 的情况
        if (is_string($entries)) {
            $decoded = json_decode($entries, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $entries = $decoded;
            }
        }

        if (!$itemId) {
            return $this->error($response, 10001, '缺少项目ID');
        }

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        if (empty($entries)) {
            return $this->error($response, 10001, '没有要保存的条目');
        }

        try {
            // 1. 删除该项目下所有永久条目
            \Illuminate\Database\Capsule\Manager::table('parameter_description_entry')
                ->where('item_id', $itemId)
                ->where('status', 'permanent')
                ->delete();

            // 2. 批量创建新的永久条目
            $createdEntries = [];
            foreach ($entries as $entry) {
                // 容错：确保是数组
                if (!is_array($entry)) {
                    continue;
                }
                // 兼容中文状态值，默认按永久处理
                $statusVal = $entry['status'] ?? 'permanent';
                if ($statusVal === '永久') {
                    $statusVal = 'permanent';
                }
                // 只保存永久条目，临时条目由前端内存管理
                if ($statusVal === 'permanent') {
                    // 计算质量评分
                    $qualityScore = $this->calculateQualityScore([
                        'description' => $entry['description'] ?? '',
                        'example' => $entry['example'] ?? '',
                        'enumValues' => $entry['enumValues'] ?? [],
                        'defaultValue' => $entry['defaultValue'] ?? '',
                        'usageCount' => $entry['usageCount'] ?? 0
                    ]);

                    $data = [
                        'id' => $this->generateId(),
                        'item_id' => $itemId,
                        'name' => $entry['name'],
                        'type' => $entry['type'],
                        'description' => $entry['description'] ?? '',
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
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    if (!empty($entry['enumValues']) && is_array($entry['enumValues'])) {
                        $data['enum_values'] = json_encode($entry['enumValues']);
                    }

                    $id = ParameterDescriptionEntry::add($data);
                    if ($id) {
                        $createdEntry = ParameterDescriptionEntry::findById($data['id']);
                        if ($createdEntry) {
                            $createdEntries[] = $this->convertEntryFields((array) $createdEntry);
                        }
                    }
                }
            }

            return $this->success($response, $createdEntries);
        } catch (\Exception $e) {
            return $this->error($response, 10001, '批量保存失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取参数描述库条目列表
     */
    private function _getEntries(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'itemId', 0);
        $keyword = $this->getParam($request, 'keyword', '');
        $status = $this->getParam($request, 'status', '');
        $name = $this->getParam($request, 'name', '');
        $type = $this->getParam($request, 'type', '');
        $tag = $this->getParam($request, 'tag', '');
        $page = $this->getParam($request, 'page', 1);
        $pageSize = $this->getParam($request, 'pageSize', 20);

        if (!$itemId) {
            return $this->error($response, 10001, '缺少项目ID');
        }

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        $conditions = ['item_id' => $itemId];

        // 构建查询
        $query = \Illuminate\Database\Capsule\Manager::table('parameter_description_entry')
            ->where('item_id', $itemId);

        // 添加搜索条件
        if ($keyword !== '') {
            $like = Security::safeLike($keyword);
            $query->where(function ($q) use ($like) {
                $q->where('name', 'LIKE', "%{$like}%")
                    ->orWhere('description', 'LIKE', "%{$like}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($name !== '') {
            $query->where('name', $name);
        }

        if ($type !== '') {
            $query->where('type', $type);
        }

        if ($tag !== '') {
            // JSON 包含特定标签（以双引号包裹）
            $tgLike = Security::safeLike('"' . $tag . '"');
            $query->where('tags', 'LIKE', "%{$tgLike}%");
        }

        // 计算总数
        $total = $query->count();

        // 获取分页数据
        $offset = ($page - 1) * $pageSize;
        $entries = $query
            ->orderBy('quality_score', 'DESC')
            ->orderBy('usage_count', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->offset($offset)
            ->limit($pageSize)
            ->get()
            ->toArray();

        // 处理JSON字段并转换字段名为前端期望的驼峰格式
        $formattedEntries = [];
        foreach ($entries as $entry) {
            $formattedEntries[] = $this->convertEntryFields((array) $entry);
        }

        return $this->success($response, [
            'data' => $formattedEntries,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize
        ]);
    }

    /**
     * 创建参数描述库条目
     */
    private function _createEntry(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $itemId = $this->getParam($request, 'itemId', 0);
        $name = $this->getParam($request, 'name', '');
        $type = $this->getParam($request, 'type', 'string');
        $description = $this->getParam($request, 'description', '');
        $example = $this->getParam($request, 'example', '');
        $enumValues = $this->getParam($request, 'enumValues', []);
        $defaultValue = $this->getParam($request, 'defaultValue', '');
        $aliases = $this->getParam($request, 'aliases', []);
        $tags = $this->getParam($request, 'tags', []);
        $path = $this->getParam($request, 'path', '');
        $source = $this->getParam($request, 'source', 'manual');
        $status = $this->getParam($request, 'status', 'permanent');

        if (!$itemId || !$name || !$description) {
            return $this->error($response, 10001, '缺少必要参数');
        }

        if (!$this->checkItemEdit($uid, $itemId)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        // 检查是否已存在相同的条目
        $existing = ParameterDescriptionEntry::findExisting($itemId, $name, $type);

        if ($existing) {
            return $this->error($response, 10001, '已存在相同的字段名和类型');
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
            'item_id' => $itemId,
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
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($enumValues)) {
            $data['enum_values'] = json_encode($enumValues);
        }

        $id = ParameterDescriptionEntry::add($data);

        if ($id) {
            // 返回创建的条目
            $entry = ParameterDescriptionEntry::findById($data['id']);

            // 字段转换和处理
            $entry = $this->convertEntryFields((array) $entry);

            return $this->success($response, $entry);
        } else {
            return $this->error($response, 10001, '创建失败');
        }
    }

    /**
     * 批量删除条目
     */
    private function _deleteEntries(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $ids = $this->getParam($request, 'ids', '');

        // 处理前端传来的逗号分隔字符串
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        } elseif (!is_array($ids)) {
            $ids = [];
        }

        if (empty($ids)) {
            return $this->error($response, 10001, '缺少要删除的ID');
        }

        // 检查权限 - 获取第一个条目的item_id进行权限验证
        $firstEntry = ParameterDescriptionEntry::findById($ids[0]);
        if (!$firstEntry) {
            return $this->error($response, 10001, '条目不存在');
        }

        if (!$this->checkItemEdit($uid, $firstEntry->item_id)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        // 构建删除条件
        $result = ParameterDescriptionEntry::delete($ids);

        if ($result > 0) {
            return $this->success($response, ['deleted' => count($ids)]);
        } else {
            return $this->error($response, 10001, '删除失败');
        }
    }

    /**
     * 更新单个条目
     */
    private function _updateEntry(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $id = $this->getParam($request, 'id', '');

        if (!$id) {
            return $this->error($response, 10001, '缺少条目ID');
        }

        // 检查条目是否存在
        $entry = ParameterDescriptionEntry::findById($id);
        if (!$entry) {
            return $this->error($response, 10001, '条目不存在');
        }

        if (!$this->checkItemEdit($uid, $entry->item_id)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        // 获取更新数据
        $updateData = [];

        $fields = ['name', 'type', 'description', 'example', 'defaultValue', 'path', 'source', 'status'];
        foreach ($fields as $field) {
            $value = $this->getParam($request, $field);
            if ($value !== null) {
                if ($field === 'defaultValue') {
                    $updateData['default_value'] = $value;
                } else {
                    $updateData[$field] = $value;
                }
            }
        }

        // 处理数组字段
        $aliases = $this->getParam($request, 'aliases');
        if ($aliases !== null) {
            $updateData['aliases'] = json_encode($aliases);
        }

        $tags = $this->getParam($request, 'tags');
        if ($tags !== null) {
            $updateData['tags'] = json_encode($tags);
        }

        $enumValues = $this->getParam($request, 'enumValues');
        if ($enumValues !== null) {
            $updateData['enum_values'] = json_encode($enumValues);
        }

        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');

            // 重新计算质量评分
            if (isset($updateData['description']) || isset($updateData['example']) || isset($updateData['enum_values']) || isset($updateData['default_value'])) {
                $entryArray = (array) $entry;
                $mergedData = array_merge($entryArray, $updateData);
                $mergedData['enumValues'] = isset($updateData['enum_values']) ? json_decode($updateData['enum_values'], true) : json_decode($entryArray['enum_values'] ?? '[]', true);
                $updateData['quality_score'] = $this->calculateQualityScore($mergedData);
            }

            $result = ParameterDescriptionEntry::update($id, $updateData);

            if ($result !== false) {
                // 返回更新后的条目
                $updatedEntry = ParameterDescriptionEntry::findById($id);

                // 字段转换和处理
                $updatedEntry = $this->convertEntryFields((array) $updatedEntry);

                return $this->success($response, $updatedEntry);
            } else {
                return $this->error($response, 10001, '更新失败');
            }
        } else {
            return $this->error($response, 10001, '没有要更新的数据');
        }
    }

    /**
     * 删除单个条目
     */
    private function _deleteEntry(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $id = $this->getParam($request, 'id', '');

        if (!$id) {
            return $this->error($response, 10001, '缺少条目ID');
        }

        // 检查条目是否存在
        $entry = ParameterDescriptionEntry::findById($id);
        if (!$entry) {
            return $this->error($response, 10001, '条目不存在');
        }

        if (!$this->checkItemEdit($uid, $entry->item_id)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        $result = ParameterDescriptionEntry::delete($id);

        if ($result > 0) {
            return $this->success($response, ['deleted' => 1]);
        } else {
            return $this->error($response, 10001, '删除失败');
        }
    }

    /**
     * 创建临时条目
     */
    public function temp(Request $request, Response $response): Response
    {
        // 临时设置 status 为 temp
        $parsedBody = $request->getParsedBody() ?: [];
        $parsedBody['status'] = 'temp';
        $request = $request->withParsedBody($parsedBody);
        return $this->_createEntry($request, $response);
    }

    /**
     * 临时条目转永久
     */
    private function _promoteTemp(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $ids = $this->getParam($request, 'ids', '');

        // 处理前端传来的逗号分隔字符串
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        } elseif (!is_array($ids)) {
            $ids = [];
        }

        if (empty($ids)) {
            return $this->error($response, 10001, '缺少要转换的ID');
        }

        // 检查权限
        $firstEntry = ParameterDescriptionEntry::findById($ids[0]);
        if (!$firstEntry) {
            return $this->error($response, 10001, '条目不存在');
        }

        if (!$this->checkItemEdit($uid, $firstEntry->item_id)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        $updated = 0;
        foreach ($ids as $entryId) {
            $result = ParameterDescriptionEntry::update($entryId, [
                'status' => 'permanent',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            if ($result > 0) {
                $updated++;
            }
        }

        if ($updated > 0) {
            return $this->success($response, ['promoted' => $updated]);
        } else {
            return $this->error($response, 10001, '转换失败');
        }
    }

    /**
     * 批量添加条目
     */
    private function _addBatchEntries(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $entries = $this->getParam($request, 'entries', []);

        if (empty($entries)) {
            return $this->error($response, 10001, '没有要添加的条目');
        }

        $results = [];
        foreach ($entries as $entry) {
            // 检查权限
            if (!$this->checkItemEdit($uid, $entry['itemId'] ?? 0)) {
                continue;
            }

            $data = [
                'id' => $this->generateId(),
                'item_id' => $entry['itemId'],
                'name' => $entry['name'],
                'type' => $entry['type'],
                'description' => $entry['description'] ?? '',
                'example' => $entry['example'] ?? '',
                'default_value' => $entry['defaultValue'] ?? '',
                'source' => $entry['source'] ?? 'manual',
                'status' => $entry['status'] ?? 'permanent',
                'usage_count' => 0,
                'quality_score' => $entry['qualityScore'] ?? 0,
                'created_by' => $uid,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $id = ParameterDescriptionEntry::add($data);
            if ($id) {
                $entryObj = ParameterDescriptionEntry::findById($data['id']);
                if ($entryObj) {
                    $results[] = $this->convertEntryFields((array) $entryObj);
                }
            }
        }

        return $this->success($response, $results);
    }

    /**
     * 确认导入
     */
    private function _confirmImport(Request $request, Response $response): Response
    {
        return $this->_addBatchEntries($request, $response);
    }

    /**
     * 更新使用次数
     */
    private function _updateUsage(Request $request, Response $response): Response
    {
        $user = [];
        if ($error = $this->requireLoginUser($request, $response, $user)) {
            return $error;
        }

        $uid = (int) ($user['uid'] ?? 0);
        $id = $this->getParam($request, 'id', '');
        $ids = $this->getParam($request, 'ids', '');

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
            return $this->error($response, 10001, '缺少要更新的ID');
        }

        // 检查权限 - 检查第一个存在的条目来验证权限
        $firstValidEntry = null;
        foreach ($ids as $entryId) {
            $entry = ParameterDescriptionEntry::findById($entryId);
            if ($entry) {
                $firstValidEntry = $entry;
                break;
            }
        }

        if (!$firstValidEntry) {
            return $this->error($response, 10001, '没有找到有效的条目');
        }

        if (!$this->checkItemEdit($uid, $firstValidEntry->item_id)) {
            return $this->error($response, 10303, '您没有编辑权限');
        }

        $updated = 0;
        $notFound = [];

        foreach ($ids as $entryId) {
            $entry = ParameterDescriptionEntry::findById($entryId);
            if ($entry) {
                // 验证该条目是否属于同一项目（安全检查）
                if ($entry->item_id != $firstValidEntry->item_id) {
                    continue; // 跳过不同项目的条目
                }

                $newUsageCount = $entry->usage_count + 1;
                $entryArray = (array) $entry;
                $newQualityScore = $this->calculateQualityScore(array_merge($entryArray, ['usageCount' => $newUsageCount]));

                $result = ParameterDescriptionEntry::update($entryId, [
                    'usage_count' => $newUsageCount,
                    'quality_score' => $newQualityScore,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                if ($result > 0) {
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

        return $this->success($response, $result);
    }

    /**
     * 解析导入数据
     */
    private function parseImportData(string $format, string $rawText): ?array
    {
        switch ($format) {
            case 'kv':
                return $this->parseKeyValue($rawText);
            case 'json':
                return $this->parseJson($rawText);
            case 'ddl':
                return $this->parseDDL($rawText);
            default:
                return null;
        }
    }

    /**
     * 解析键值对格式
     */
    private function parseKeyValue(string $text): array
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
    private function parseJson(string $text): ?array
    {
        $data = json_decode($text, true);
        if (!$data) return null;

        return $this->extractFieldsFromObject($data);
    }

    /**
     * 从对象中提取字段
     */
    private function extractFieldsFromObject($obj, string $prefix = ''): array
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
            } elseif (is_object($value) || (is_array($value) && !empty($value))) {
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
    private function parseDDL(string $text): array
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
    private function calculateQualityScore(array $data): float
    {
        $score = 0.0;

        // 基础分数
        if (!empty($data['description'])) $score += 0.4;
        if (!empty($data['example'])) $score += 0.2;
        if (!empty($data['enumValues']) && is_array($data['enumValues']) && count($data['enumValues']) > 0) $score += 0.1;
        if (!empty($data['defaultValue'])) $score += 0.1;

        // 使用频次加分
        $usageCount = $data['usageCount'] ?? ($data['usage_count'] ?? 0);
        $usageScore = min($usageCount * 0.02, 0.2);
        $score += $usageScore;

        return min($score, 1.0);
    }

    /**
     * 生成唯一ID
     */
    private function generateId(): string
    {
        return uniqid() . '_' . mt_rand(1000, 9999);
    }

    /**
     * 转换数据库字段名为前端驼峰格式
     */
    private function convertEntryFields(array $entry): array
    {
        // 处理JSON字段
        $entry['aliases'] = !empty($entry['aliases']) ? json_decode($entry['aliases'], true) : [];
        $entry['tags'] = !empty($entry['tags']) ? json_decode($entry['tags'], true) : [];
        $entry['enumValues'] = !empty($entry['enum_values']) ? json_decode($entry['enum_values'], true) : [];

        // 字段名转换：数据库下划线 -> 前端驼峰
        $entry['itemId'] = $entry['item_id'] ?? 0;
        $entry['usageCount'] = (int) ($entry['usage_count'] ?? 0);
        $entry['qualityScore'] = (float) ($entry['quality_score'] ?? 0);
        $entry['defaultValue'] = $entry['default_value'] ?? '';
        $entry['createdBy'] = $entry['created_by'] ?? 0;
        $entry['updatedAt'] = isset($entry['updated_at']) ? strtotime($entry['updated_at']) * 1000 : 0; // 转换为前端时间戳格式

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
}
