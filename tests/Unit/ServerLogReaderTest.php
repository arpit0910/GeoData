<?php

namespace Tests\Unit;

use App\Services\ServerLogReader;
use PHPUnit\Framework\TestCase;

class ServerLogReaderTest extends TestCase
{
    private function read(string $content, string $level = '', string $search = ''): array
    {
        $path = tempnam(sys_get_temp_dir(), 'log-reader-');
        try {
            file_put_contents($path, $content);
            return (new ServerLogReader())->read($path, $level, $search);
        } finally {
            unlink($path);
        }
    }

    public function test_multiline_errors_remain_separate_with_their_complete_traces(): void
    {
        $result = $this->read("[2026-10-29 00:00:00] production.ERROR: First failure\n[stacktrace]\n#0 first.php\n\nCaused by: nested failure\n[2026-10-29 00:01:00] production.ERROR: Second failure\n#0 second.php\n");
        $this->assertSame(2, $result['totalEntries']);
        $this->assertSame(2, $result['levelCounts']['error']);
        $this->assertSame('Second failure', $result['entries'][0]['message']);
        $this->assertStringNotContainsString('first.php', $result['entries'][0]['details']);
        $this->assertStringContainsString("\n\nCaused by:", $result['entries'][1]['details']);
        $this->assertSame(6, $result['entries'][0]['line']);
    }

    public function test_filters_search_complete_entries_including_older_traces(): void
    {
        $content = "[2026-10-29 00:00:00] production.ERROR: Older failure\n#0 unique-needle.php\n";
        foreach (range(1, 150) as $number) {
            $content .= "[2026-10-29 00:01:00] production.INFO: Message {$number}\n";
        }
        $result = $this->read($content, 'error', 'UNIQUE-NEEDLE');
        $this->assertSame(151, $result['totalEntries']);
        $this->assertSame(1, $result['matchingEntries']);
        $this->assertSame('Older failure', $result['entries'][0]['message']);
        $this->assertSame(150, $result['levelCounts']['info']);
    }

    public function test_retention_limit_keeps_complete_newest_events_and_bounds_large_details(): void
    {
        $content = '';
        foreach (range(1, 105) as $number) {
            $content .= "[2026-10-29 00:00:00] production.ERROR: Failure {$number}\n#0 trace {$number}\n";
        }
        $content .= str_repeat('x', 70000)."\nsearch-past-display-limit\n";
        $result = $this->read($content);
        $this->assertCount(100, $result['entries']);
        $this->assertSame('Failure 105', $result['entries'][0]['message']);
        $this->assertSame('Failure 6', $result['entries'][99]['message']);
        $this->assertTrue($result['entries'][0]['truncated']);
        $this->assertLessThanOrEqual(65536, strlen($result['entries'][0]['details']));
        $this->assertSame(1, $this->read($content, '', 'search-past-display-limit')['matchingEntries']);
    }

    public function test_unstructured_content_and_empty_files_are_supported(): void
    {
        $this->assertSame([], $this->read('')['entries']);
        $result = $this->read("plain output\nmore context\n[2026-10-29 00:00:00] local.WARNING: Attention\n");
        $this->assertSame('warning', $result['entries'][0]['level']);
        $this->assertSame('unknown', $result['entries'][1]['level']);
        $this->assertStringContainsString('more context', $result['entries'][1]['details']);
    }
}
