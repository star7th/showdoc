#!/usr/bin/env php
<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../../autoload.php')) {
    require __DIR__ . '/../../../autoload.php';
} elseif (file_exists(__DIR__ . '/../autoload.php')) {
    require __DIR__ . '/../autoload.php';
} else {
    throw new RuntimeException('Unable to locate autoload.php file.');
}

use JmesPath\Env;
use JmesPath\DebugRuntime;

$description = <<<EOT
Runs a JMESPath expression on the provided input or a test case.

Provide the JSON input and expression:
    echo '{}' | jp.php expression

Or provide the path to a compliance script, a suite, and test case number:
    jp.php --script path_to_script --suite test_suite_number --case test_case_number [expression]

EOT;

$args = [];
$currentKey = null;
for ($i = 1, $total = count($argv); $i < $total; $i++) {
    if ($i % 2) {
        if (substr($argv[$i], 0, 2) == '--') {
            $currentKey = str_replace('--', '', $argv[$i]);
        } else {
            $currentKey = trim($argv[$i]);
        }
    } else {
        $args[$currentKey] = $argv[$i];
        $currentKey = null;
    }
}

$expression = $currentKey;

if (isset($args['file']) || isset($args['suite']) || isset($args['case'])) {
    if (!isset($args['file']) || !isset($args['suite']) || !isset($args['case'])) {
        die($description);
    }
    // Manually run a compliance test
    $path = realpath($args['file']);
    file_exists($path) or die('File not found at ' . $path);
    $json = json_decode(file_get_contents($path), true);
    $set = $json[$args['suite']];
    $data = $set['given'];
    if (!isset($expression)) {
        $expression = $set['cases'][$args['case']]['expression'];
        echo "Expects\n=======\n";
        if (isset($set['cases'][$args['case']]['result'])) {
            echo json_encode($set['cases'][$args['case']]['result'], JSON_PRETTY_PRINT) . "\n\n";
        } elseif (isset($set['cases'][$args['case']]['error'])) {
            echo "{$set['cases'][$argv['case']]['error']} error\n\n";
        } else {
            echo "NULL\n\n";
        }
    }
} elseif (isset($expression)) {
    // Pass in an expression and STDIN as a standalone argument
    $data = json_decode(stream_get_contents(STDIN), true);
} else {
    die($description);
}

$runtime = new DebugRuntime(Env::createRuntime());
$runtime($expression, $data);
