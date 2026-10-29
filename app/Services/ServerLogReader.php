<?php

namespace App\Services;

use Illuminate\Support\Str;
use SplFileObject;

class ServerLogReader
{
    public const MAX_ENTRIES = 100;
    private const MAX_ENTRY_BYTES = 65536;
    public const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    public function read(string $path, string $level = '', string $search = ''): array
    {
        $counts = array_fill_keys(self::LEVELS, 0);
        $entries = [];
        $current = null;
        $total = 0;
        $matched = 0;
        $lineNumber = 0;
        $file = new SplFileObject($path, 'rb');

        $finish = function () use (&$current, &$counts, &$entries, &$total, &$matched, $level) {
            if ($current === null) {
                return;
            }
            $total++;
            $counts[$current['level']] = ($counts[$current['level']] ?? 0) + 1;
            if (($level === '' || $current['level'] === $level) && $current['matches']) {
                $entries[$matched % self::MAX_ENTRIES] = $current;
                $matched++;
            }
        };

        while (! $file->eof()) {
            $line = $file->fgets();
            if ($line === '') {
                continue;
            }
            $lineNumber++;
            $line = mb_convert_encoding($line, 'UTF-8', 'UTF-8');
            if (preg_match('/^\[([^\]\r\n]+)\]\s+([^\s]+)\.(emergency|alert|critical|error|warning|notice|info|debug):\s?(.*)$/i', rtrim($line, "\r\n"), $header)) {
                $finish();
                $current = [
                    'timestamp' => $header[1],
                    'channel' => $header[2],
                    'level' => strtolower($header[3]),
                    'message' => Str::limit($header[4], 240),
                    'line' => $lineNumber,
                    'details' => '',
                    'truncated' => false,
                    'matches' => $search === '',
                ];
            } elseif ($current === null) {
                if (trim($line) === '') {
                    continue;
                }
                $current = [
                    'timestamp' => null, 'channel' => null, 'level' => 'unknown',
                    'message' => Str::limit(trim($line), 240), 'line' => $lineNumber,
                    'details' => '', 'truncated' => false, 'matches' => $search === '',
                ];
            }

            // Search all lines, including context beyond the display size limit.
            $current['matches'] = $current['matches'] || ($search !== '' && mb_stripos($line, $search) !== false);
            $remaining = self::MAX_ENTRY_BYTES - strlen($current['details']);
            if (strlen($line) > $remaining) {
                $current['truncated'] = true;
            }
            if ($remaining > 0) {
                $current['details'] .= mb_strcut($line, 0, $remaining, 'UTF-8');
            }
        }
        $finish();

        // A circular buffer bounds memory while preserving the newest matching entries.
        if ($matched > self::MAX_ENTRIES) {
            $start = $matched % self::MAX_ENTRIES;
            $entries = array_merge(array_slice($entries, $start), array_slice($entries, 0, $start));
        }

        return [
            'entries' => array_reverse(array_values($entries)),
            'totalEntries' => $total,
            'matchingEntries' => $matched,
            'levelCounts' => $counts,
        ];
    }
}
