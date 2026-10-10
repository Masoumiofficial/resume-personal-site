<?php

// Turns PHPUnit JUnit failures into GitHub "::error" annotations (file + line).
// Usage: php .github/scripts/junit-annotate.php junit.xml "<job label>"
[$_, $file, $label] = $argv + [null, 'junit.xml', 'tests'];

if (! is_file($file)) {
    echo "::error title={$label}::PHPUnit produced no JUnit report (crash before tests ran?)\n";
    exit(0);
}

$xml = simplexml_load_file($file);
$count = 0;
foreach ($xml->xpath('//testcase[failure or error]') as $case) {
    $node = $case->failure ?? $case->error;
    $message = trim((string) $node);
    $firstLine = strtok($message, "\n") ?: 'failed';
    $path = (string) $case['file'];
    $line = (int) $case['line'];

    if (preg_match('#(/[^\s:]+\.php):(\d+)#', $message, $m) && str_contains($m[1], '/tests/')) {
        [$path, $line] = [$m[1], (int) $m[2]];
    }

    $relative = preg_replace('#^.*?/(tests|app|routes|config|database)/#', '$1/', $path);
    $escape = fn (string $s) => str_replace(['%', "\r", "\n"], ['%25', '%0D', '%0A'], $s);
    printf("::error file=%s,line=%d,title=%s::%s%%0A%s\n",
        $relative, $line, $escape("{$label}: {$case['class']}::{$case['name']}"), $escape($firstLine), $escape(mb_substr($message, 0, 1500)));
    $count++;
}

echo "{$count} failing test(s) annotated.\n";
