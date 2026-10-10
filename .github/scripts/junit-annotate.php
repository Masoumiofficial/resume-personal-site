<?php

// Turns PHPUnit JUnit failures into GitHub "::error" annotations (file + line).
// Usage: php .github/scripts/junit-annotate.php junit.xml "<job label>"
//
// Never fails itself: a broken report must not hide the real test failure.
// (PHPUnit can write characters that are invalid in XML, e.g. U+FFFF from a
// rendered exception page, so the report is sanitised before parsing.)
$file = $argv[1] ?? 'junit.xml';
$label = $argv[2] ?? 'tests';

$data = function (string $s): string {   // annotation message
    return str_replace(['%', "\r", "\n"], ['%25', '%0D', '%0A'], $s);
};
$prop = function (string $s) use ($data): string {   // annotation property (title, file)
    return str_replace([':', ','], ['%3A', '%2C'], $data($s));
};

$raw = is_file($file) ? (string) file_get_contents($file) : '';
if ($raw === '') {
    echo '::error title='.$prop($label)."::PHPUnit produced no JUnit report (it crashed before finishing).\n";
    exit(0);
}

$clean = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $raw) ?? $raw;
$xml = @simplexml_load_string($clean, 'SimpleXMLElement', LIBXML_PARSEHUGE | LIBXML_NONET);
if ($xml === false) {
    echo '::error title='.$prop($label)."::Could not parse the JUnit report; see the PHPUnit step output.\n";
    exit(0);
}

$count = 0;
foreach ($xml->xpath('//testcase[failure or error]') ?: [] as $case) {
    $node = isset($case->failure) ? $case->failure : $case->error;
    $message = trim((string) $node);
    // Drop rendered HTML exception pages; keep the readable part.
    $message = preg_replace('/<!DOCTYPE html>.*$/s', '[HTML exception page omitted]', $message) ?? $message;
    $firstLine = strtok($message, "\n") ?: 'failed';
    $path = (string) $case['file'];
    $line = (int) $case['line'];

    if (preg_match_all('#(/[^\s:]+/tests/[^\s:]+\.php):(\d+)#', $message, $m, PREG_SET_ORDER)) {
        $last = end($m);
        [$path, $line] = [$last[1], (int) $last[2]];
    }

    $relative = preg_replace('#^.*?/(tests|app|routes|config|database)/#', '$1/', $path);
    printf(
        "::error file=%s,line=%d,title=%s::%s%%0A%s\n",
        $prop($relative), $line, $prop("{$label} - {$case['class']} > {$case['name']}"),
        $data($firstLine), $data(mb_substr($message, 0, 2000))
    );
    $count++;
}

echo "{$count} failing test(s) annotated.\n";
