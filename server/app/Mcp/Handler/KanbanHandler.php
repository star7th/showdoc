<?php

namespace App\Mcp\Handler;

use App\Mcp\McpHandler;
use App\Mcp\McpError;
use App\Mcp\McpException;
use App\Model\Page;
use App\Model\Item;
use Illuminate\Database\Capsule\Manager as DB;

class KanbanHandler extends McpHandler
{
  private function logActivity(int $itemId, int $pageId, string $eventType, array $eventData = [], int $operatorUid = 0): void
  {
    DB::table('kanban_activity_log')->insert([
      'item_id' => $itemId,
      'page_id' => $pageId,
      'event_type' => $eventType,
      'event_data' => json_encode($eventData, JSON_UNESCAPED_UNICODE),
      'operator_uid' => $operatorUid ?: $this->getUid(),
      'addtime' => time(),
    ]);
  }

  private function findBoardPage(int $itemId): ?object
  {
    return DB::table('page')
      ->where('item_id', $itemId)
      ->where('page_title', '__kanban_board__')
      ->where('is_del', 0)
      ->first();
  }

  private function decodeContent(string $rawContent): ?array
  {
    $content = htmlspecialchars_decode($rawContent, ENT_QUOTES);
    return json_decode($content, true);
  }

  private function saveBoardData(int $itemId, int $pageId, array $boardData): void
  {
    $content = json_encode($boardData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    Page::savePage($pageId, $itemId, [
      'page_content' => $content,
      'addtime' => time(),
      'author_uid' => $this->getUid(),
      'author_username' => $this->getUsername(),
    ]);
    Page::deleteCache($pageId);
    Item::deleteCache($itemId);
  }

  private function saveTaskData(int $itemId, int $pageId, string $title, array $taskData): void
  {
    $content = json_encode($taskData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    $extInfo = json_encode([
      'completed' => (bool) ($taskData['completed'] ?? false),
      'tags' => $taskData['tags'] ?? [],
    ], JSON_UNESCAPED_UNICODE);
    Page::savePage($pageId, $itemId, [
      'page_content' => $content,
      'page_title' => $title,
      'ext_info' => $extInfo,
      'addtime' => time(),
      'author_uid' => $this->getUid(),
      'author_username' => $this->getUsername(),
    ]);
    Page::deleteCache($pageId);
  }

  private function getUsername(): string
  {
    $uid = $this->getUid();
    if ($uid <= 0) {
      return '';
    }
    $user = \App\Model\User::findById($uid);
    return $user ? ($user->username ?? '') : '';
  }

  private function findPage(int $pageId): array
  {
    $page = DB::table('page')
      ->where('page_id', $pageId)
      ->where('is_del', 0)
      ->first();
    if (!$page) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '页面不存在');
    }
    return ['page' => $page, 'itemId' => (int) $page->item_id];
  }

  private function requireBoard(int $itemId): array
  {
    $boardPage = $this->findBoardPage($itemId);
    if (!$boardPage) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '看板不存在，请确认项目是否为看板类型');
    }
    $boardData = $this->decodeContent($boardPage->page_content);
    if (!$boardData) {
      McpError::throw(McpError::OPERATION_FAILED, '看板数据解析失败');
    }
    return [
      'boardPage' => $boardPage,
      'boardData' => $boardData,
      'pageId' => (int) $boardPage->page_id,
    ];
  }

  private function loadTaskPageIds(array $boardData): array
  {
    $pageIds = [];
    $tasksOrder = $boardData['tasks_order'] ?? [];
    foreach ($tasksOrder as $listTasks) {
      if (is_array($listTasks)) {
        foreach ($listTasks as $pid) {
          $pageIds[] = (int) $pid;
        }
      }
    }
    return array_unique($pageIds);
  }

  private function completeTaskInList(int $itemId, int $pageId): void
  {
    $task = $this->loadTaskContent($pageId, $itemId);
    if ($task) {
      $td = $task['taskData'];
      $td['completed'] = true;
      $td['updated_at'] = time();
      $this->saveTaskData($itemId, $pageId, $task['pageTitle'], $td);
    }
  }

  private function loadTaskContent(int $pageId, int $itemId): ?array
  {
    $page = DB::table('page')
      ->where('page_id', $pageId)
      ->where('is_del', 0)
      ->first();
    if (!$page) {
      return null;
    }
    $taskData = $this->decodeContent($page->page_content);
    if (!$taskData) {
      return null;
    }
    return [
      'page' => $page,
      'taskData' => $taskData,
      'pageId' => (int) $page->page_id,
      'pageTitle' => $page->page_title,
    ];
  }

  private function findListName(array $boardData, string $listId): string
  {
    $lists = $boardData['lists'] ?? [];
    foreach ($lists as $list) {
      if (($list['id'] ?? '') === $listId) {
        return $list['title'] ?? '';
      }
    }
    return '';
  }

  private function listExists(array $boardData, string $listId): bool
  {
    $lists = $boardData['lists'] ?? [];
    foreach ($lists as $list) {
      if (($list['id'] ?? '') === $listId) {
        return true;
      }
    }
    return false;
  }

  private function removeTaskFromList(array &$boardData, int $pageId): ?string
  {
    $tasksOrder = &$boardData['tasks_order'];
    $foundList = null;
    foreach ($tasksOrder as $listId => &$tasks) {
      if (is_array($tasks)) {
        $idx = array_search($pageId, $tasks);
        if ($idx !== false) {
          array_splice($tasks, $idx, 1);
          $foundList = $listId;
          break;
        }
      }
    }
    unset($tasks);
    return $foundList;
  }

  private function validateTags(array $tags): void
  {
    if (count($tags) > 3) {
      McpError::throw(McpError::INVALID_PARAMS, '标签最多3个');
    }
    $allowedColors = ['red', 'orange', 'yellow', 'green', 'blue', 'purple', 'gray'];
    foreach ($tags as $tag) {
      if (isset($tag['color']) && !in_array($tag['color'], $allowedColors)) {
        McpError::throw(McpError::INVALID_PARAMS, '标签颜色不合法，可选值: ' . implode(', ', $allowedColors));
      }
      if (isset($tag['text']) && mb_strlen($tag['text']) > 20) {
        McpError::throw(McpError::INVALID_PARAMS, '标签文本不能超过20个字符');
      }
    }
  }

  private function validatePriority(string $priority): void
  {
    if ($priority !== '' && !in_array($priority, ['high', 'medium', 'low'])) {
      McpError::throw(McpError::INVALID_PARAMS, '优先级不合法，可选值: high, medium, low');
    }
  }

  public function getSupportedOperations(): array
  {
    return [
      'kanban_get_board',
      'kanban_get_lists',
      'kanban_get_task',
      'kanban_list_tasks',
      'kanban_search_tasks',
      'kanban_create_task',
      'kanban_update_task',
      'kanban_move_task',
      'kanban_delete_task',
      'kanban_add_list',
      'kanban_update_list',
      'kanban_delete_list',
      'kanban_archive_list',
      'kanban_restore_list',
      'kanban_list_archived_lists',
      'kanban_get_activity',
    ];
  }

  public function execute(string $operation, array $params = [])
  {
    switch ($operation) {
      case 'kanban_get_board':
        return $this->getBoard($params);
      case 'kanban_get_lists':
        return $this->getLists($params);
      case 'kanban_get_task':
        return $this->getTask($params);
      case 'kanban_list_tasks':
        return $this->listTasks($params);
      case 'kanban_search_tasks':
        return $this->searchTasks($params);
      case 'kanban_create_task':
        return $this->createTask($params);
      case 'kanban_update_task':
        return $this->updateTask($params);
      case 'kanban_move_task':
        return $this->moveTask($params);
      case 'kanban_delete_task':
        return $this->deleteTask($params);
      case 'kanban_add_list':
        return $this->addList($params);
      case 'kanban_update_list':
        return $this->updateList($params);
      case 'kanban_delete_list':
        return $this->deleteList($params);
      case 'kanban_archive_list':
        return $this->archiveList($params);
      case 'kanban_restore_list':
        return $this->restoreList($params);
      case 'kanban_list_archived_lists':
        return $this->listArchivedLists($params);
      case 'kanban_get_activity':
        return $this->getActivity($params);
      default:
        McpError::throw(McpError::METHOD_NOT_FOUND, "操作不存在: {$operation}");
    }
  }

  private function getBoard(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }
    $this->requireReadPermission($itemId);

    $board = $this->requireBoard($itemId);
    $boardData = $board['boardData'];
    $lists = $boardData['lists'] ?? [];
    $tasksOrder = $boardData['tasks_order'] ?? [];

    $resultLists = [];
    foreach ($lists as $list) {
      $listId = $list['id'] ?? '';
      $taskIds = array_unique($tasksOrder[$listId] ?? []);
      $tasks = [];
      $seenPageIds = [];
      foreach ($taskIds as $pid) {
        $pid = (int) $pid;
        if (in_array($pid, $seenPageIds, true)) {
          continue;
        }
        $seenPageIds[] = $pid;
        $pageRow = DB::table('page')
          ->where('page_id', $pid)
          ->where('is_del', 0)
          ->first();
        if ($pageRow) {
          $tasks[] = [
            'page_id' => $pid,
            'page_title' => $pageRow->page_title,
          ];
        }
      }
      $resultLists[] = [
        'id' => $listId,
        'title' => $list['title'] ?? '',
        'position' => (int) ($list['position'] ?? 0),
        'tasks' => $tasks,
      ];
    }

    return [
      'item_id' => $itemId,
      'page_id' => $board['pageId'],
      'lists' => $resultLists,
      'archived_lists' => $boardData['archived_lists'] ?? [],
    ];
  }

  private function getLists(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }
    $this->requireReadPermission($itemId);

    $board = $this->requireBoard($itemId);
    $boardData = $board['boardData'];
    $lists = $boardData['lists'] ?? [];
    $tasksOrder = $boardData['tasks_order'] ?? [];
    $archivedLists = $boardData['archived_lists'] ?? [];
    $archivedTasksOrder = $boardData['archived_tasks_order'] ?? [];

    $resultLists = [];
    foreach ($lists as $list) {
      $listId = $list['id'] ?? '';
      $taskCount = count(array_unique($tasksOrder[$listId] ?? []));
      $resultLists[] = [
        'id' => $listId,
        'title' => $list['title'] ?? '',
        'position' => (int) ($list['position'] ?? 0),
        'task_count' => $taskCount,
      ];
    }

    $resultArchived = [];
    foreach ($archivedLists as $list) {
      $listId = $list['id'] ?? '';
      $taskCount = count(array_unique($archivedTasksOrder[$listId] ?? []));
      $resultArchived[] = [
        'id' => $listId,
        'title' => $list['title'] ?? '',
        'position' => (int) ($list['position'] ?? 0),
        'task_count' => $taskCount,
      ];
    }

    return [
      'item_id' => $itemId,
      'lists' => $resultLists,
      'archived_lists' => $resultArchived,
    ];
  }

  private function getTask(array $params): array
  {
    $pageId = (int) ($params['page_id'] ?? 0);
    if ($pageId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '任务ID不能为空');
    }

    $found = $this->findPage($pageId);
    $page = $found['page'];
    $itemId = $found['itemId'];

    $this->requireReadPermission($itemId);

    $taskData = $this->decodeContent($page->page_content);
    if (!$taskData) {
      McpError::throw(McpError::OPERATION_FAILED, '任务数据解析失败');
    }

    $listId = $taskData['list_id'] ?? '';
    $listName = '';
    $board = $this->findBoardPage($itemId);
    if ($board) {
      $boardData = $this->decodeContent($board->page_content);
      if ($boardData) {
        $listName = $this->findListName($boardData, $listId);
      }
    }

    return [
      'page_id' => $pageId,
      'page_title' => $page->page_title,
      'item_id' => $itemId,
      'list_id' => $listId,
      'list_name' => $listName,
      'description' => $taskData['description'] ?? '',
      'assignee_uid' => $taskData['assignee_uid'] ?? '',
      'assignee_username' => $taskData['assignee_username'] ?? '',
      'creator_uid' => $taskData['creator_uid'] ?? '',
      'creator_username' => $taskData['creator_username'] ?? '',
      'due_date' => $taskData['due_date'] ?? '',
      'tags' => $taskData['tags'] ?? [],
      'priority' => $taskData['priority'] ?? '',
      'linked_pages' => $taskData['linked_pages'] ?? [],
      'completed' => (bool) ($taskData['completed'] ?? false),
      'created_at' => (int) ($taskData['created_at'] ?? 0),
      'updated_at' => (int) ($taskData['updated_at'] ?? 0),
    ];
  }

  private function listTasks(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }
    $this->requireReadPermission($itemId);

    $board = $this->requireBoard($itemId);
    $boardData = $board['boardData'];
    $allPageIds = $this->loadTaskPageIds($boardData);

    $filterListId = $params['list_id'] ?? '';
    $filterAssigneeUid = $params['assignee_uid'] ?? '';
    $filterCreatorUid = (int) ($params['creator_uid'] ?? 0);
    $filterTag = $params['tag'] ?? '';
    $filterPriority = $params['priority'] ?? '';
    $filterDueDateStart = $params['due_date_start'] ?? '';
    $filterDueDateEnd = $params['due_date_end'] ?? '';
    $filterNoDueDate = !empty($params['no_due_date']);
    $showCompleted = !empty($params['show_completed']);

    $result = [];
    foreach ($allPageIds as $pid) {
      $task = $this->loadTaskContent($pid, $itemId);
      if (!$task) {
        continue;
      }
      $td = $task['taskData'];
      $isCompleted = (bool) ($td['completed'] ?? false);

      if ($isCompleted && !$showCompleted) {
        continue;
      }
      if ($filterListId !== '' && ($td['list_id'] ?? '') !== $filterListId) {
        continue;
      }
      if ($filterAssigneeUid === 'none') {
        if (!empty($td['assignee_uid'])) {
          continue;
        }
      } elseif ($filterAssigneeUid !== '' && (int) $filterAssigneeUid > 0 && (int) ($td['assignee_uid'] ?? 0) !== (int) $filterAssigneeUid) {
        continue;
      }
      if ($filterCreatorUid > 0 && (int) ($td['creator_uid'] ?? 0) !== $filterCreatorUid) {
        continue;
      }
      if ($filterTag !== '') {
        $tags = $td['tags'] ?? [];
        $found = false;
        foreach ($tags as $t) {
          if (($t['text'] ?? '') === $filterTag) {
            $found = true;
            break;
          }
        }
        if (!$found) {
          continue;
        }
      }
      if ($filterPriority !== '' && ($td['priority'] ?? '') !== $filterPriority) {
        continue;
      }
      if ($filterNoDueDate && !empty($td['due_date'])) {
        continue;
      }
      if (($filterDueDateStart !== '' || $filterDueDateEnd !== '') && !$filterNoDueDate) {
        $dueDate = $td['due_date'] ?? '';
        if ($dueDate === '') {
          continue;
        }
        if ($filterDueDateStart !== '' && $dueDate < $filterDueDateStart) {
          continue;
        }
        if ($filterDueDateEnd !== '' && $dueDate > $filterDueDateEnd) {
          continue;
        }
      }

      $result[] = [
        'page_id' => $task['pageId'],
        'page_title' => $task['pageTitle'],
        'item_id' => $itemId,
        'list_id' => $td['list_id'] ?? '',
        'description' => $td['description'] ?? '',
        'assignee_uid' => $td['assignee_uid'] ?? '',
        'assignee_username' => $td['assignee_username'] ?? '',
        'creator_uid' => $td['creator_uid'] ?? '',
        'creator_username' => $td['creator_username'] ?? '',
        'due_date' => $td['due_date'] ?? '',
        'tags' => $td['tags'] ?? [],
        'priority' => $td['priority'] ?? '',
        'linked_pages' => $td['linked_pages'] ?? [],
        'completed' => $isCompleted,
        'created_at' => (int) ($td['created_at'] ?? 0),
        'updated_at' => (int) ($td['updated_at'] ?? 0),
      ];
    }

    return [
      'item_id' => $itemId,
      'tasks' => $result,
      'total' => count($result),
    ];
  }

  private function searchTasks(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }
    $query = trim($params['query'] ?? '');
    if ($query === '') {
      McpError::throw(McpError::INVALID_PARAMS, '搜索关键字不能为空');
    }
    $this->requireReadPermission($itemId);

    $pages = DB::table('page')
      ->where('item_id', $itemId)
      ->where('is_del', 0)
      ->where('page_title', 'like', "%{$query}%")
      ->where('page_title', '<>', '__kanban_board__')
      ->limit(50)
      ->get(['page_id', 'page_title'])
      ->all();

    $board = $this->findBoardPage($itemId);
    $boardData = null;
    if ($board) {
      $boardData = $this->decodeContent($board->page_content);
    }

    $result = [];
    foreach ($pages as $p) {
      $task = $this->loadTaskContent((int) $p->page_id, $itemId);
      if (!$task) {
        continue;
      }
      $td = $task['taskData'];
      $listName = '';
      if ($boardData) {
        $listName = $this->findListName($boardData, $td['list_id'] ?? '');
      }
      $result[] = [
        'page_id' => (int) $p->page_id,
        'page_title' => $p->page_title,
        'list_id' => $td['list_id'] ?? '',
        'list_name' => $listName,
        'priority' => $td['priority'] ?? '',
        'assignee_username' => $td['assignee_username'] ?? '',
        'due_date' => $td['due_date'] ?? '',
      ];
    }

    return [
      'item_id' => $itemId,
      'query' => $query,
      'tasks' => $result,
      'total' => count($result),
    ];
  }

  private function createTask(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }
    $listId = $params['list_id'] ?? '';
    if ($listId === '') {
      McpError::throw(McpError::INVALID_PARAMS, '列表ID不能为空');
    }
    $title = trim($params['title'] ?? '');
    if ($title === '') {
      McpError::throw(McpError::INVALID_PARAMS, '任务标题不能为空');
    }
    if (mb_strlen($title) > 100) {
      McpError::throw(McpError::INVALID_PARAMS, '任务标题不能超过100个字符');
    }

    $this->requireWritePermission($itemId);

    $board = $this->requireBoard($itemId);
    $boardData = $board['boardData'];

    if (!$this->listExists($boardData, $listId)) {
      McpError::throw(McpError::INVALID_PARAMS, '目标列表不存在');
    }

    $now = time();
    $uid = $this->getUid();
    $username = $this->getUsername();

    $tags = $params['tags'] ?? [];
    $this->validateTags($tags);
    $priority = $params['priority'] ?? 'medium';
    $this->validatePriority($priority);

    $taskData = [
      'list_id' => $listId,
      'description' => $params['description'] ?? '',
      'assignee_uid' => '',
      'assignee_username' => '',
      'creator_uid' => $uid,
      'creator_username' => $username,
      'due_date' => $params['due_date'] ?? '',
      'tags' => $tags,
      'priority' => $priority,
      'linked_pages' => $params['linked_pages'] ?? [],
      'completed' => false,
    ];

    $content = json_encode($taskData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

    $data = [
      'page_title' => $title,
      'page_content' => $content,
      'ext_info' => json_encode(['completed' => false, 'tags' => $tags], JSON_UNESCAPED_UNICODE),
      'item_id' => $itemId,
      'cat_id' => 0,
      's_number' => 99,
      'addtime' => $now,
      'author_uid' => $uid,
      'author_username' => $username,
    ];

    $pageId = Page::addPage($itemId, $data);
    if ($pageId <= 0) {
      McpError::throw(McpError::OPERATION_FAILED, '创建任务失败');
    }

    if (!isset($boardData['tasks_order'][$listId])) {
      $boardData['tasks_order'][$listId] = [];
    }
    $boardData['tasks_order'][$listId][] = $pageId;

    $this->saveBoardData($itemId, $board['pageId'], $boardData);

    DB::table('item')->where('item_id', $itemId)->update(['last_update_time' => $now]);
    Item::deleteCache($itemId);

    $this->logActivity($itemId, $pageId, 'task_created', ['title' => $title, 'list_id' => $listId]);

    return [
      'page_id' => $pageId,
      'page_title' => $title,
      'item_id' => $itemId,
      'list_id' => $listId,
      'message' => '任务创建成功',
    ];
  }

  private function updateTask(array $params): array
  {
    $pageId = (int) ($params['page_id'] ?? 0);
    if ($pageId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '任务ID不能为空');
    }

    $found = $this->findPage($pageId);
    $page = $found['page'];
    $itemId = $found['itemId'];

    $this->requireWritePermission($itemId);

    $taskData = $this->decodeContent($page->page_content);
    if (!$taskData) {
      McpError::throw(McpError::OPERATION_FAILED, '任务数据解析失败');
    }

    $title = $params['title'] ?? null;
    if ($title !== null) {
      $title = trim($title);
      if ($title === '') {
        McpError::throw(McpError::INVALID_PARAMS, '任务标题不能为空');
      }
      if (mb_strlen($title) > 100) {
        McpError::throw(McpError::INVALID_PARAMS, '任务标题不能超过100个字符');
      }
    } else {
      $title = $page->page_title;
    }

    $updated = false;
    if (array_key_exists('description', $params)) {
      $taskData['description'] = $params['description'];
      $updated = true;
    }
    if (array_key_exists('assignee_uid', $params)) {
      $taskData['assignee_uid'] = $params['assignee_uid'];
      $updated = true;
    }
    if (array_key_exists('assignee_username', $params)) {
      $taskData['assignee_username'] = $params['assignee_username'];
      $updated = true;
    }
    if (array_key_exists('due_date', $params)) {
      $taskData['due_date'] = $params['due_date'];
      $updated = true;
    }
    if (array_key_exists('tags', $params)) {
      $this->validateTags($params['tags']);
      $taskData['tags'] = $params['tags'];
      $updated = true;
    }
    if (array_key_exists('priority', $params)) {
      $this->validatePriority($params['priority']);
      $taskData['priority'] = $params['priority'];
      $updated = true;
    }
    if (array_key_exists('linked_pages', $params)) {
      $taskData['linked_pages'] = $params['linked_pages'];
      $updated = true;
    }
    if (array_key_exists('completed', $params)) {
      $taskData['completed'] = (bool) $params['completed'];
      $updated = true;
    }

    if (!$updated && $title === $page->page_title) {
      McpError::throw(McpError::INVALID_PARAMS, '没有需要更新的内容');
    }

    $taskData['updated_at'] = time();
    $this->saveTaskData($itemId, $pageId, $title, $taskData);

    DB::table('item')->where('item_id', $itemId)->update(['last_update_time' => time()]);
    Item::deleteCache($itemId);

    if (array_key_exists('completed', $params)) {
      $eventType = $taskData['completed'] ? 'task_completed' : 'task_uncompleted';
      $this->logActivity($itemId, $pageId, $eventType, ['title' => $title]);
    } else {
      $this->logActivity($itemId, $pageId, 'task_updated', ['title' => $title]);
    }

    return [
      'page_id' => $pageId,
      'page_title' => $title,
      'item_id' => $itemId,
      'message' => '任务更新成功',
    ];
  }

  private function moveTask(array $params): array
  {
    $pageId = (int) ($params['page_id'] ?? 0);
    if ($pageId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '任务ID不能为空');
    }
    $targetListId = $params['target_list_id'] ?? '';
    if ($targetListId === '') {
      McpError::throw(McpError::INVALID_PARAMS, '目标列表ID不能为空');
    }

    $found = $this->findPage($pageId);
    $page = $found['page'];
    $itemId = $found['itemId'];

    $this->requireWritePermission($itemId);

    $board = $this->requireBoard($itemId);
    $boardData = $board['boardData'];

    if (!$this->listExists($boardData, $targetListId)) {
      McpError::throw(McpError::INVALID_PARAMS, '目标列表不存在');
    }

    $taskData = $this->decodeContent($page->page_content);
    if (!$taskData) {
      McpError::throw(McpError::OPERATION_FAILED, '任务数据解析失败');
    }

    $oldListId = $taskData['list_id'] ?? '';
    $taskData['list_id'] = $targetListId;
    $taskData['updated_at'] = time();
    $this->saveTaskData($itemId, $pageId, $page->page_title, $taskData);

    $this->removeTaskFromList($boardData, $pageId);
    if (!isset($boardData['tasks_order'][$targetListId])) {
      $boardData['tasks_order'][$targetListId] = [];
    }
    $boardData['tasks_order'][$targetListId][] = $pageId;

    $this->saveBoardData($itemId, $board['pageId'], $boardData);

    DB::table('item')->where('item_id', $itemId)->update(['last_update_time' => time()]);
    Item::deleteCache($itemId);

    $fromListTitle = $this->findListName($boardData, $oldListId);
    $toListTitle = $this->findListName($boardData, $targetListId);
    $this->logActivity($itemId, $pageId, 'task_moved', [
      'title' => $page->page_title,
      'from_list_id' => $oldListId,
      'to_list_id' => $targetListId,
      'from_list_title' => $fromListTitle,
      'to_list_title' => $toListTitle,
    ]);

    return [
      'page_id' => $pageId,
      'item_id' => $itemId,
      'from_list_id' => $oldListId,
      'to_list_id' => $targetListId,
      'message' => '任务移动成功',
    ];
  }

  private function deleteTask(array $params): array
  {
    $pageId = (int) ($params['page_id'] ?? 0);
    if ($pageId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '任务ID不能为空');
    }

    $found = $this->findPage($pageId);
    $itemId = $found['itemId'];

    $this->requireWritePermission($itemId);

    $board = $this->requireBoard($itemId);
    $boardData = $board['boardData'];

    $this->removeTaskFromList($boardData, $pageId);
    $this->saveBoardData($itemId, $board['pageId'], $boardData);

    DB::table('page')->where('page_id', $pageId)->update(['is_del' => 1]);
    Page::deleteCache($pageId);

    DB::table('item')->where('item_id', $itemId)->update(['last_update_time' => time()]);
    Item::deleteCache($itemId);

    $this->logActivity($itemId, $pageId, 'task_deleted', ['title' => $found['page']->page_title ?? '']);

    return [
      'page_id' => $pageId,
      'message' => '任务已删除',
    ];
  }

  private function addList(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }
    $title = trim($params['title'] ?? '');
    if ($title === '') {
      McpError::throw(McpError::INVALID_PARAMS, '列表标题不能为空');
    }

    $this->requireWritePermission($itemId);

    $board = $this->requireBoard($itemId);
    $boardData = $board['boardData'];

    $maxPosition = 0;
    foreach ($boardData['lists'] ?? [] as $list) {
      $pos = (int) ($list['position'] ?? 0);
      if ($pos > $maxPosition) {
        $maxPosition = $pos;
      }
    }

    $newListId = 'list_' . intval(microtime(true) * 1000) . '_' . mt_rand(1000, 9999);
    $boardData['lists'][] = [
      'id' => $newListId,
      'title' => $title,
      'position' => $maxPosition + 1,
    ];
    $boardData['tasks_order'][$newListId] = [];

    $this->saveBoardData($itemId, $board['pageId'], $boardData);

    DB::table('item')->where('item_id', $itemId)->update(['last_update_time' => time()]);
    Item::deleteCache($itemId);

    $this->logActivity($itemId, 0, 'list_created', ['list_id' => $newListId, 'title' => $title]);

    return [
      'item_id' => $itemId,
      'list_id' => $newListId,
      'title' => $title,
      'position' => $maxPosition + 1,
      'message' => '列表创建成功',
    ];
  }

  private function updateList(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }
    $listId = $params['list_id'] ?? '';
    if ($listId === '') {
      McpError::throw(McpError::INVALID_PARAMS, '列表ID不能为空');
    }
    $title = trim($params['title'] ?? '');
    if ($title === '') {
      McpError::throw(McpError::INVALID_PARAMS, '列表标题不能为空');
    }

    $this->requireWritePermission($itemId);

    $board = $this->requireBoard($itemId);
    $boardData = $board['boardData'];

    $found = false;
    foreach ($boardData['lists'] as &$list) {
      if (($list['id'] ?? '') === $listId) {
        $list['title'] = $title;
        $found = true;
        break;
      }
    }
    unset($list);

    if (!$found) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '列表不存在');
    }

    $this->saveBoardData($itemId, $board['pageId'], $boardData);

    DB::table('item')->where('item_id', $itemId)->update(['last_update_time' => time()]);
    Item::deleteCache($itemId);

    $this->logActivity($itemId, 0, 'list_updated', ['list_id' => $listId, 'title' => $title]);

    return [
      'item_id' => $itemId,
      'list_id' => $listId,
      'title' => $title,
      'message' => '列表更新成功',
    ];
  }

  private function deleteList(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }
    $listId = $params['list_id'] ?? '';
    if ($listId === '') {
      McpError::throw(McpError::INVALID_PARAMS, '列表ID不能为空');
    }

    $this->requireWritePermission($itemId);

    $board = $this->requireBoard($itemId);
    $boardData = $board['boardData'];

    $listExists = false;
    foreach ($boardData['lists'] ?? [] as $list) {
      if (($list['id'] ?? '') === $listId) {
        $listExists = true;
        break;
      }
    }
    if (!$listExists) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '列表不存在');
    }

    if (count($boardData['lists'] ?? []) <= 1) {
      McpError::throw(McpError::INVALID_PARAMS, '唯一列表不允许删除');
    }

    $now = time();
    $taskIds = $boardData['tasks_order'][$listId] ?? [];
    foreach ($taskIds as $pid) {
      $pid = (int) $pid;
      $this->completeTaskInList($itemId, $pid);
    }

    $boardData['lists'] = array_values(array_filter($boardData['lists'] ?? [], function ($list) use ($listId) {
      return ($list['id'] ?? '') !== $listId;
    }));
    unset($boardData['tasks_order'][$listId]);

    $this->saveBoardData($itemId, $board['pageId'], $boardData);

    DB::table('item')->where('item_id', $itemId)->update(['last_update_time' => $now]);
    Item::deleteCache($itemId);

    $this->logActivity($itemId, 0, 'list_deleted', ['list_id' => $listId, 'completed_tasks' => count($taskIds)]);

    return [
      'item_id' => $itemId,
      'list_id' => $listId,
      'completed_tasks_count' => count($taskIds),
      'message' => '列表已删除，列表内任务已标记为完成',
    ];
  }

  private function archiveList(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }
    $listId = $params['list_id'] ?? '';
    if ($listId === '') {
      McpError::throw(McpError::INVALID_PARAMS, '列表ID不能为空');
    }

    $this->requireWritePermission($itemId);

    $board = $this->requireBoard($itemId);
    $boardData = $board['boardData'];

    $targetList = null;
    foreach ($boardData['lists'] ?? [] as $list) {
      if (($list['id'] ?? '') === $listId) {
        $targetList = $list;
        break;
      }
    }
    if (!$targetList) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '列表不存在');
    }

    $taskCount = count($boardData['tasks_order'][$listId] ?? []);

    if (!isset($boardData['archived_lists'])) {
      $boardData['archived_lists'] = [];
    }
    if (!isset($boardData['archived_tasks_order'])) {
      $boardData['archived_tasks_order'] = [];
    }

    $boardData['archived_lists'][] = $targetList;
    $boardData['archived_tasks_order'][$listId] = $boardData['tasks_order'][$listId] ?? [];

    $boardData['lists'] = array_values(array_filter($boardData['lists'] ?? [], function ($list) use ($listId) {
      return ($list['id'] ?? '') !== $listId;
    }));
    unset($boardData['tasks_order'][$listId]);

    $this->saveBoardData($itemId, $board['pageId'], $boardData);

    $now = time();
    DB::table('item')->where('item_id', $itemId)->update(['last_update_time' => $now]);
    Item::deleteCache($itemId);

    $this->logActivity($itemId, 0, 'list_archived', ['list_id' => $listId, 'title' => $targetList['title'] ?? '', 'tasks_count' => $taskCount]);

    return [
      'item_id' => $itemId,
      'list_id' => $listId,
      'list_title' => $targetList['title'] ?? '',
      'archived_tasks_count' => $taskCount,
      'message' => "列表「{$targetList['title']}」已归档，包含{$taskCount}个任务",
    ];
  }

  private function restoreList(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }
    $listId = $params['list_id'] ?? '';
    if ($listId === '') {
      McpError::throw(McpError::INVALID_PARAMS, '列表ID不能为空');
    }

    $this->requireWritePermission($itemId);

    $board = $this->requireBoard($itemId);
    $boardData = $board['boardData'];

    $targetList = null;
    $archivedLists = $boardData['archived_lists'] ?? [];
    foreach ($archivedLists as $list) {
      if (($list['id'] ?? '') === $listId) {
        $targetList = $list;
        break;
      }
    }
    if (!$targetList) {
      McpError::throw(McpError::RESOURCE_NOT_FOUND, '已归档列表中不存在该列表');
    }

    $taskCount = count($boardData['archived_tasks_order'][$listId] ?? []);

    $boardData['lists'][] = $targetList;
    $boardData['tasks_order'][$listId] = $boardData['archived_tasks_order'][$listId] ?? [];

    $boardData['archived_lists'] = array_values(array_filter($boardData['archived_lists'] ?? [], function ($list) use ($listId) {
      return ($list['id'] ?? '') !== $listId;
    }));
    unset($boardData['archived_tasks_order'][$listId]);

    $this->saveBoardData($itemId, $board['pageId'], $boardData);

    $now = time();
    DB::table('item')->where('item_id', $itemId)->update(['last_update_time' => $now]);
    Item::deleteCache($itemId);

    $this->logActivity($itemId, 0, 'list_restored', ['list_id' => $listId, 'title' => $targetList['title'] ?? '', 'tasks_count' => $taskCount]);

    return [
      'item_id' => $itemId,
      'list_id' => $listId,
      'list_title' => $targetList['title'] ?? '',
      'restored_tasks_count' => $taskCount,
      'message' => "列表「{$targetList['title']}」已恢复，包含{$taskCount}个任务",
    ];
  }

  private function listArchivedLists(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }
    $this->requireReadPermission($itemId);

    $board = $this->requireBoard($itemId);
    $boardData = $board['boardData'];

    $archivedLists = $boardData['archived_lists'] ?? [];
    $archivedTasksOrder = $boardData['archived_tasks_order'] ?? [];

    $result = [];
    foreach ($archivedLists as $list) {
      $listId = $list['id'] ?? '';
      $taskIds = $archivedTasksOrder[$listId] ?? [];
      $result[] = [
        'id' => $listId,
        'title' => $list['title'] ?? '',
        'position' => (int) ($list['position'] ?? 0),
        'tasks_count' => count($taskIds),
      ];
    }

    return [
      'item_id' => $itemId,
      'archived_lists' => $result,
      'total' => count($result),
    ];
  }

  private function getActivity(array $params): array
  {
    $itemId = (int) ($params['item_id'] ?? 0);
    if ($itemId <= 0) {
      McpError::throw(McpError::INVALID_PARAMS, '项目ID不能为空');
    }
    $this->requireReadPermission($itemId);

    $query = DB::table('kanban_activity_log')
      ->where('item_id', $itemId);

    $eventTypes = $params['event_types'] ?? [];
    if (is_array($eventTypes) && !empty($eventTypes)) {
      $query->whereIn('event_type', $eventTypes);
    }

    $startTime = (int) ($params['start_time'] ?? 0);
    $endTime = (int) ($params['end_time'] ?? 0);
    if ($startTime > 0) {
      $query->where('addtime', '>=', $startTime);
    }
    if ($endTime > 0) {
      $query->where('addtime', '<=', $endTime);
    }

    $page = max(1, (int) ($params['page'] ?? 1));
    $pageSize = min(100, max(1, (int) ($params['page_size'] ?? 50)));
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
        $userObj = \App\Model\User::findById($uid);
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

    return [
      'item_id' => $itemId,
      'activities' => $result,
      'total' => $total,
      'page' => $page,
      'page_size' => $pageSize,
    ];
  }
}
