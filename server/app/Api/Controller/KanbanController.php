<?php

namespace App\Api\Controller;

use App\Common\BaseController;
use App\Model\Item;
use App\Model\User;
use Illuminate\Database\Capsule\Manager as DB;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class KanbanController extends BaseController
{
    private const ALLOWED_EVENT_TYPES = [
        'task_created',
        'task_completed',
        'task_uncompleted',
        'task_moved',
        'task_deleted',
        'task_updated',
        'list_created',
        'list_updated',
        'list_deleted',
        'list_archived',
        'list_restored',
    ];

    private const EVENT_TYPE_LABELS = [
        'task_created' => '创建任务',
        'task_completed' => '完成任务',
        'task_uncompleted' => '取消完成',
        'task_moved' => '移动任务',
        'task_deleted' => '删除任务',
        'task_updated' => '更新任务',
        'list_created' => '创建列表',
        'list_updated' => '更新列表',
        'list_deleted' => '删除列表',
        'list_archived' => '归档列表',
        'list_restored' => '恢复列表',
    ];

    private function requireKanbanItem(int $itemId): ?object
    {
        if ($itemId <= 0) {
            return null;
        }
        $item = Item::findById($itemId);
        if (!$item || (int) $item->item_type !== 6) {
            return null;
        }
        return $item;
    }

    public function logEvent(Request $request, Response $response): Response
    {
        $loginUser = [];
        if ($error = $this->requireLoginUser($request, $response, $loginUser)) {
            return $error;
        }

        $itemId = (int) $this->getParam($request, 'item_id', 0);
        $pageId = (int) $this->getParam($request, 'page_id', 0);
        $eventType = (string) $this->getParam($request, 'event_type', '');
        $eventData = $this->getParam($request, 'event_data');

        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目ID不能为空');
        }
        if ($eventType === '' || !in_array($eventType, self::ALLOWED_EVENT_TYPES, true)) {
            return $this->error($response, 10101, '事件类型不合法');
        }

        $item = $this->requireKanbanItem($itemId);
        if (!$item) {
            return $this->error($response, 10303, '项目不存在或不是看板项目');
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10303, '无权访问该项目');
        }

        if (is_string($eventData)) {
            $eventData = json_decode($eventData, true) ?: [];
        }
        if (!is_array($eventData)) {
            $eventData = [];
        }

        DB::table('kanban_activity_log')->insert([
            'item_id' => $itemId,
            'page_id' => $pageId,
            'event_type' => $eventType,
            'event_data' => json_encode($eventData, JSON_UNESCAPED_UNICODE),
            'operator_uid' => $uid,
            'addtime' => time(),
        ]);

        return $this->success($response, []);
    }

    private function buildActivityQuery(Request $request, int $itemId)
    {
        $query = DB::table('kanban_activity_log')
            ->where('item_id', $itemId);

        $eventTypes = $this->getParam($request, 'event_types');
        if (is_string($eventTypes)) {
            $decoded = json_decode($eventTypes, true);
            if (is_array($decoded)) {
                $eventTypes = $decoded;
            }
        }
        if (is_array($eventTypes) && !empty($eventTypes)) {
            $query->whereIn('event_type', $eventTypes);
        } elseif (is_string($eventTypes) && $eventTypes !== '') {
            $query->where('event_type', $eventTypes);
        }

        $startTime = (int) $this->getParam($request, 'start_time', 0);
        $endTime = (int) $this->getParam($request, 'end_time', 0);
        if ($startTime > 0) {
            $query->where('addtime', '>=', $startTime);
        }
        if ($endTime > 0) {
            $query->where('addtime', '<=', $endTime);
        }

        return $query;
    }

    public function getActivity(Request $request, Response $response): Response
    {
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);

        $itemId = (int) $this->getParam($request, 'item_id', 0);
        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目ID不能为空');
        }

        $item = $this->requireKanbanItem($itemId);
        if (!$item) {
            return $this->error($response, 10303, '项目不存在或不是看板项目');
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10303, '无权访问该项目');
        }

        $query = $this->buildActivityQuery($request, $itemId);

        $page = max(1, (int) $this->getParam($request, 'page', 1));
        $pageSize = min(100, max(1, (int) $this->getParam($request, 'page_size', 50)));
        $total = $query->count();

        $logs = $query->orderBy('addtime', 'desc')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get()
            ->all();

        $operatorUids = array_unique(array_map(fn($log) => (int) $log->operator_uid, $logs));
        $operatorNames = [];
        foreach ($operatorUids as $uid) {
            if ($uid > 0) {
                $userObj = User::findById($uid);
                if ($userObj) {
                    $operatorNames[$uid] = $userObj->username;
                }
            }
        }

        $result = [];
        foreach ($logs as $log) {
            $uid = (int) $log->operator_uid;
            $result[] = [
                'id' => (int) $log->id,
                'item_id' => (int) $log->item_id,
                'page_id' => (int) $log->page_id,
                'event_type' => $log->event_type,
                'event_data' => json_decode($log->event_data, true) ?? [],
                'operator_uid' => $uid,
                'operator_username' => $operatorNames[$uid] ?? '',
                'addtime' => (int) $log->addtime,
            ];
        }

        return $this->success($response, [
            'item_id' => $itemId,
            'activities' => $result,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ]);
    }

    public function exportActivity(Request $request, Response $response): Response
    {
        $loginUser = [];
        $this->requireLoginUser($request, $response, $loginUser, false);

        $itemId = (int) $this->getParam($request, 'item_id', 0);
        if ($itemId <= 0) {
            return $this->error($response, 10101, '项目ID不能为空');
        }

        $item = $this->requireKanbanItem($itemId);
        if (!$item) {
            return $this->error($response, 10303, '项目不存在或不是看板项目');
        }

        $uid = (int) ($loginUser['uid'] ?? 0);
        if (!$this->checkItemVisit($uid, $itemId)) {
            return $this->error($response, 10303, '无权访问该项目');
        }

        $query = $this->buildActivityQuery($request, $itemId);
        $logs = $query->orderBy('addtime', 'desc')->get()->all();

        $operatorUids = array_unique(array_map(fn($log) => (int) $log->operator_uid, $logs));
        $operatorNames = [];
        foreach ($operatorUids as $uid) {
            if ($uid > 0) {
                $userObj = User::findById($uid);
                if ($userObj) {
                    $operatorNames[$uid] = $userObj->username;
                }
            }
        }

        $csvOutput = fopen('php://temp', 'r+');
        fprintf($csvOutput, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($csvOutput, ['操作者', '操作', '对象', '详情', '时间']);

        foreach ($logs as $log) {
            $uid = (int) $log->operator_uid;
            $eventData = json_decode($log->event_data, true) ?? [];

            $object = '';
            if (!empty($eventData['title'])) {
                $object = $eventData['title'];
            }

            $detail = '';
            if (!empty($eventData['from_list_title']) && !empty($eventData['to_list_title'])) {
                $detail = $eventData['from_list_title'] . ' → ' . $eventData['to_list_title'];
            }

            fputcsv($csvOutput, [
                $operatorNames[$uid] ?? '',
                self::EVENT_TYPE_LABELS[$log->event_type] ?? $log->event_type,
                $object,
                $detail,
                date('Y-m-d H:i:s', (int) $log->addtime),
            ]);
        }

        rewind($csvOutput);
        $csvContent = stream_get_contents($csvOutput);
        fclose($csvOutput);

        $fileName = 'kanban_activity_' . $itemId . '.csv';

        $response = $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment;filename="' . $fileName . '"')
            ->withHeader('Cache-Control', 'max-age=0');

        $response->getBody()->write($csvContent);
        return $response;
    }
}
