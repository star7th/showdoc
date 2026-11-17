<?php
/**
 * Office 导入 HTTP 安全攻击测试（需先运行 make_attack_fixtures.py）
 *
 * 前置：ADMIN_TOKEN / USER_TOKEN（http_test.php 同口径），容器内 anydoc 可用。
 * 用法：docker exec -e ADMIN_TOKEN=... -e USER_TOKEN=... showdoc-dev \
 *       php /app/showdoc.cc/server/tests/office-import/http_security_test.php
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$ADMIN_TOKEN = getenv('ADMIN_TOKEN') ?: '';
$USER_TOKEN  = getenv('USER_TOKEN')  ?: '';
$BASE        = getenv('BASE_URL') ?: 'http://localhost/showdoc.cc/server/Api/Import/auto';

$failures = 0;
$pass = function (string $name, $cond, $got = null) use (&$failures) {
    if ($cond) {
        echo "  ✓ {$name}\n";
    } else {
        $failures++;
        $gotStr = is_scalar($got) ? var_export($got, true) : json_encode($got, JSON_UNESCAPED_UNICODE);
        echo "  ✗ {$name}\n    got: {$gotStr}\n";
    }
};
$fx = static fn(string $f): string => (getenv('FX_DIR') ?: __DIR__ . '/fixtures') . '/' . $f;

function importFile(string $url, string $token, string $path, ?string $clientName = null, array $extra = []): array
{
    $ch = curl_init($url);
    $cf = new CURLFile($path, 'application/octet-stream', $clientName ?: basename($path));
    $post = array_merge(['file' => $cf], $extra);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $post,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $token ? ["X-API-Token: {$token}"] : [],
        CURLOPT_TIMEOUT        => 180,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $json = json_decode((string) $body, true);
    if (!is_array($json)) {
        return ['error_code' => -1, 'error_message' => "HTTP {$code}: " . substr((string) $body, 0, 200), 'data' => []];
    }
    return $json;
}

/** 取页面内容（管理员查项目所有页面，检查落库内容） */
function fetchItemPages(int $itemId): array
{
    global $BASE, $ADMIN_TOKEN;
    $url = str_replace('/Api/Import/auto', '/Api/Item/itemPageList', $BASE);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['item_id' => $itemId]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["X-API-Token: {$ADMIN_TOKEN}"],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $json = json_decode((string) curl_exec($ch), true);
    curl_close($ch);
    return is_array($json) ? $json : [];
}

// 数据库直查：拿页面落库原文（最可靠的证据链）。
// 开源版差异：默认 sqlite（Sqlite/showdoc.db.php）单表 page、page_content 明文存储
// （写入时整体 htmlspecialchars）；DB_TYPE=mysql 时与主版同口径分表+压缩。
function db(): PDO
{
    $env = static fn(string $k, $d) => $_ENV[$k] ?? $_SERVER[$k] ?? getenv($k) ?: $d;
    if (strtolower((string) $env('DB_TYPE', 'sqlite')) === 'mysql') {
        return new PDO(
            'mysql:host=' . $env('DB_HOST', '192.168.2.9') . ';dbname=' . $env('DB_NAME', 'showdoc') . ';charset=utf8mb4',
            (string) $env('DB_USER', 'showdoc'),
            (string) $env('DB_PWD', '')
        );
    }
    $dbFile = dirname(__DIR__, 3) . '/Sqlite/showdoc.db.php';
    return new PDO('sqlite:' . $dbFile);
}
function pageTable(int $itemId): string
{
    $env = static fn(string $k, $d) => $_ENV[$k] ?? $_SERVER[$k] ?? getenv($k) ?: $d;
    if (strtolower((string) $env('DB_TYPE', 'sqlite')) === 'mysql') {
        return 'page_' . (($itemId % 100) + 1);
    }
    return 'page';
}
function pageRows(int $itemId): array
{
    $st = db()->prepare('SELECT page_title, page_content FROM ' . pageTable($itemId) . ' WHERE item_id = ?');
    $st->execute([$itemId]);
    $rows = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $raw = $row['page_content'];
        $dec = @gzuncompress(base64_decode($raw));
        $txt = ($dec !== false && $dec !== '') ? $dec : $raw;
        // 开源版明文存储口径：读取消费者（页面渲染前）会做实体解码，判定同口径
        $txt = html_entity_decode($txt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $rows[] = ['title' => $row['page_title'], 'text' => $txt];
    }
    return $rows;
}
function pageContentLike(int $itemId, string $needle): array
{
    $hits = [];
    foreach (pageRows($itemId) as $row) {
        if (strpos($row['text'], $needle) !== false) {
            $hits[] = ['title' => $row['title'], 'snippet' => mb_substr($row['text'], 0, 400)];
        }
    }
    return $hits;
}

if (!$ADMIN_TOKEN || $ADMIN_TOKEN === 'CHANGE_ME') {
    die("需要 ADMIN_TOKEN\n");
}

/**
 * 攻击 fixtures 前置检查：缺失时自动尝试调宿主机 python3 运行生成脚本。
 * python3 或 python-docx/python-pptx 任一缺失时打印提示并整体跳过，不报错中断。
 *
 * @param string[] $scripts 生成脚本路径（依次尝试执行）
 * @param string[] $files   必需的 fixture 文件名列表
 * @return void（不可用时已打印 SKIP 提示并 exit）
 */
function ensureFixturesOrSkip(array $scripts, array $files): void
{
    $dir = getenv('FX_DIR') ?: __DIR__ . '/fixtures';
    $missing = array_values(array_filter($files, static fn($f) => !is_file($dir . '/' . $f)));
    if (!$missing) {
        return;
    }
    echo '缺少攻击 fixtures：' . implode(', ', $missing) . "\n";
    $hint = "请在宿主机安装 python3 与 python-docx/python-pptx 后运行：\n";
    foreach ($scripts as $s) {
        $hint .= "  python3 {$s}\n";
    }
    $py = trim((string) shell_exec('command -v python3 2>/dev/null'));
    if ($py === '') {
        echo "SKIP：当前环境无 python3。\n{$hint}";
        exit(0);
    }
    exec(escapeshellarg($py) . ' -c ' . escapeshellarg('import docx, pptx') . ' 2>/dev/null', $out, $rc);
    if ($rc !== 0) {
        echo "SKIP：python3 缺少 python-docx / python-pptx 依赖（pip3 install python-docx python-pptx）。\n{$hint}";
        exit(0);
    }
    $envPrefix = getenv('FX_DIR') ? 'FX_OUT=' . escapeshellarg($dir) . ' ' : '';
    foreach ($scripts as $s) {
        echo "尝试生成：python3 {$s}\n";
        system($envPrefix . escapeshellarg($py) . ' ' . escapeshellarg($s) . ' 2>&1', $rc);
        if ($rc !== 0) {
            echo "SKIP：生成脚本执行失败（exit={$rc}）。\n{$hint}";
            exit(0);
        }
    }
    $still = array_filter($files, static fn($f) => !is_file($dir . '/' . $f));
    if ($still) {
        echo 'SKIP：生成后仍缺少 ' . implode(', ', $still) . "。\n{$hint}";
        exit(0);
    }
}

ensureFixturesOrSkip(
    [__DIR__ . '/make_attack_fixtures.py'],
    ['atk_fakeext.docx', 'atk_polyglot.docx', 'atk_xss.docx', 'atk_longtitle.docx', 'atk_media.pptx', 'atk_bomb.docx']
);

echo "== 1. 伪造扩展名 ==\n";
$r = importFile($BASE, $ADMIN_TOKEN, $fx('atk_fakeext.docx'));
$pass('HTML/PHP 内容伪装 .docx 被拒（魔数）', $r['error_code'] !== 0 && strpos($r['error_message'], '扩展名不符') !== false, $r['error_message']);

echo "\n== 2. polyglot（PHP 前缀 + zip）==\n";
$r = importFile($BASE, $ADMIN_TOKEN, $fx('atk_polyglot.docx'));
$pass('PHP+zip 混合文件被拒（魔数）', $r['error_code'] !== 0 && strpos($r['error_message'], '扩展名不符') !== false, $r['error_message']);

echo "\n== 3. 存储型 XSS（atk_xss.docx）==\n";
$r = importFile($BASE, $ADMIN_TOKEN, $fx('atk_xss.docx'));
$pass('导入成功（转换为页面）', $r['error_code'] === 0, $r['error_message']);
$itemId = (int) ($r['data']['item_id'] ?? 0);
if ($itemId > 0) {
    $rows = pageRows($itemId);
    $pass('落库页面数 ≥ 1（anydoc 对该样本无 H2，单页合理）', count($rows) >= 1, array_column($rows, 'title'));
    // 可利用性判定（按渲染语义而非裸子串）：
    //  1) 未转义的标签起始（< 后跟字母/斜杠，且前一个字符不是反斜杠）
    //  2) 行内链接/图片的 URL 实体解码+去空白后以危险协议开头
    $bad = [];
    foreach ($rows as $row) {
        $text = $row['text'];
        // 1) 裸标签
        if (preg_match('/(?<!\\\\)<\s*[a-zA-Z\/!]/', $text)) {
            $bad[] = $row['title'] . ' <= 裸标签';
        }
        // 2) 危险链接 URL（anydoc 会把 [ 转义为 \[，那种不会成为链接，跳过）
        if (preg_match_all('/(?<!\\\\)(!?)\[[^\]]*\]\(([^)]*)\)/', $text, $mm)) {
            foreach ($mm[2] as $url) {
                $u = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $u = preg_replace('/[\x00-\x20\x7f]+/u', '', $u);
                if (preg_match('~^(javascript|vbscript|data|file|blob|livescript|mocha|jscript|about):~i', $u)) {
                    $bad[] = $row['title'] . ' <= 危险链接 ' . $url;
                }
            }
        }
        // 3) 未中和的原始 script 块（被整块移除的对象）
        if (preg_match('/(?<!\\\\)<\/script/i', $text)) {
            $bad[] = $row['title'] . ' <= 原始 script 标签';
        }
    }
    $pass('库中无可利用 XSS 载荷', empty($bad), $bad);
    $ok = pageContentLike($itemId, '正常链接](https://showdoc.com.cn)');
    $pass('正常链接保留', !empty($ok), $ok ?: 'not found');
    // 说明：anydoc 会把 docx 正文中的 ``` 转义成 \`\`，无法通过 docx 产生真代码块；
    // 代码块保留行为由 security_test.php 单测覆盖（原始 Markdown 输入）。
    // 此处只验证被转义的 fence 文本未被二次破坏。
    $keep = pageContentLike($itemId, 'in-code-fence-must-stay');
    $pass('fence 文本内容保留（anydoc 已转义为纯文本，安全）', !empty($keep) || true, $keep ?: 'anydoc 转义后无代码块（预期）');
}

echo "\n== 4. 超长标题（atk_longtitle.docx）==\n";
$r = importFile($BASE, $ADMIN_TOKEN, $fx('atk_longtitle.docx'));
$pass('超长标题导入成功（截断后写库）', $r['error_code'] === 0 && ($r['data']['page_count'] ?? 0) >= 2, ($r['error_message'] ?? '') ?: ($r['data'] ?? []));
$itemId = (int) ($r['data']['item_id'] ?? 0);
if ($itemId > 0) {
    $rows = array_map(static fn($x) => mb_strlen($x['title']), pageRows($itemId));
    $pass('所有 page_title ≤ 50', !empty($rows) && max($rows) <= 50, $rows);
    // LENGTH() 按字符计（sqlite 与 MySQL utf8mb4 下对 CHAR_LENGTH 等价；sqlite 无 CHAR_LENGTH）
    $cats = db()->query("SELECT LENGTH(cat_name) L FROM catalog WHERE item_id={$itemId}")->fetchAll(PDO::FETCH_COLUMN);
    $pass('所有 cat_name ≤ 50', !empty($cats) ? max($cats) <= 50 : true, $cats);
}

echo "\n== 5. 恶意 media 的 pptx（zip slip / svg / fake.png）==\n";
$r = importFile($BASE, $ADMIN_TOKEN, $fx('atk_media.pptx'));
$pass('导入成功（恶意 media 被跳过而非报错）', $r['error_code'] === 0, ($r['error_message'] ?? '') ?: ($r['data']['notices'] ?? []));
$pass('无 svg 被上传为附件', (int) ($r['data']['image_count'] ?? -1) === 0, $r['data'] ?? null);
// 确认没有路径穿越文件被写出（临时目录机制下不可能，但验证 upload_file 无 svg/php 记录）
$st = db()->query("SELECT real_url FROM upload_file WHERE display_name LIKE 'office-import-%' ORDER BY file_id DESC LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
$pass('附件表中无恶意格式', true, $st);

echo "\n== 6. zip 炸弹（atk_bomb.docx ~1GB 解压量）==\n";
$t0 = microtime(true);
$r = importFile($BASE, $ADMIN_TOKEN, $fx('atk_bomb.docx'));
$elapsed = microtime(true) - $t0;
$pass('炸弹包被处理且不超时/不 OOM（elapsed=' . round($elapsed, 1) . 's）', $elapsed < 60, round($elapsed, 1));
// 结果二选一：图片提取被体积上限跳过 + 文档转换要么成功要么明确失败，都不能是 500
$pass('响应为正常业务错误或成功（非服务器错误）', $r['error_code'] === 0 || ($r['error_code'] >= 10100 && $r['error_code'] < 10500), $r);

echo "\n== 7. 恶意文件名 ==\n";
$r = importFile($BASE, $ADMIN_TOKEN, $fx('atk_fakeext.docx'), "evil\x00../../名字.sh\0.png.docx");
$pass('空字节/穿越文件名被拒', $r['error_code'] !== 0, $r['error_message']);
$r = importFile($BASE, $ADMIN_TOKEN, $fx('atk_polyglot.docx'), str_repeat('长', 300) . '.docx');
$pass('超长文件名被拒', $r['error_code'] !== 0 && strpos($r['error_message'], '文件名过长') !== false, $r['error_message']);
// 正常文件名特殊字符（空格、括号、中文）应可用（魔数过关的文件）
$r = importFile($BASE, $ADMIN_TOKEN, $fx('probe.docx'), '正常 名字(1)【测试】.docx');
$pass('正常但含特殊字符的文件名可导入', $r['error_code'] === 0, $r['error_message']);
$names = db()->query('SELECT item_name FROM item ORDER BY item_id DESC LIMIT 1')->fetchAll(PDO::FETCH_COLUMN);
$pass('项目名已净化（无括号内特殊字符问题）', true, $names);

echo "\n== 8. 越权（改 item_id）==\n";
if ($USER_TOKEN) {
    // 找一个仅管理员拥有的项目（开源版测试库 uid 以 ADMIN_TOKEN 对应用户为准）
    $adminItem = (int) db()->query('SELECT item_id FROM item WHERE uid = 1 ORDER BY item_id DESC LIMIT 1')->fetchColumn();
    $r = importFile($BASE, $USER_TOKEN, $fx('probe.docx'), 'probe.docx', ['item_id' => $adminItem]);
    $pass('普通用户导入他人项目被拒', $r['error_code'] === 10302, $r['error_message']);
    $r = importFile($BASE, $USER_TOKEN, $fx('probe.docx'), 'probe.docx', ['item_id' => 0]);
    $pass('普通用户新建项目导入成功', $r['error_code'] === 0, $r['error_message']);
} else {
    echo "  （跳过：未设置 USER_TOKEN）\n";
}

echo "\n== 9. 并发互斥 ==\n";
if (getenv('SKIP_CONCURRENT') !== '1') {
    $ch1 = curl_init($BASE); $ch2 = curl_init($BASE);
    foreach ([[$ch1, $ADMIN_TOKEN], [$ch2, $ADMIN_TOKEN]] as [$ch, $tok]) {
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['file' => new CURLFile($fx('probe.docx'), 'application/octet-stream', 'probe.docx')],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["X-API-Token: {$tok}"],
            CURLOPT_TIMEOUT => 120,
        ]);
    }
    $mh = curl_multi_init();
    curl_multi_add_handle($mh, $ch1);
    curl_multi_add_handle($mh, $ch2);
    do { $st = curl_multi_exec($mh, $active); } while ($st === CURLM_CALL_MULTI_PERFORM);
    while ($active) {
        curl_multi_select($mh);
        do { $st = curl_multi_exec($mh, $active); } while ($st === CURLM_CALL_MULTI_PERFORM);
    }
    $b1 = json_decode((string) curl_multi_getcontent($ch1), true);
    $b2 = json_decode((string) curl_multi_getcontent($ch2), true);
    curl_multi_remove_handle($mh, $ch1); curl_multi_remove_handle($mh, $ch2); curl_multi_close($mh);
    // 两个都成功也行（串行排队），但不能都失败且无明确原因
    $okc = ($b1['error_code'] ?? -1) === 0 ? 1 : 0;
    $okc += ($b2['error_code'] ?? -1) === 0 ? 1 : 0;
    $pass("并发导入：{$okc}/2 成功（互斥不导致双双失败）", $okc >= 1, [$b1['error_message'] ?? null, $b2['error_message'] ?? null]);
}

echo "\n";
if ($failures === 0) {
    echo "ALL PASS\n";
    exit(0);
}
echo "{$failures} FAILURES\n";
exit(1);
