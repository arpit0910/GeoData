<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileObject;
use Throwable;

class LogController extends Controller
{
    private const MAX_VISIBLE_LINES = 1200;

    public function index(Request $request)
    {
        $logDirectory = storage_path('logs');

        $files = collect(File::exists($logDirectory) ? File::files($logDirectory) : [])
            ->filter(fn ($file) => Str::endsWith($file->getFilename(), '.log'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        $selectedFilename = $request->string('file')->toString();
        $selectedFile = $files->first(function ($file) use ($selectedFilename) {
            return $selectedFilename
                ? $file->getFilename() === $selectedFilename
                : true;
        });

        $levelCounts = [
            'emergency' => 0,
            'alert' => 0,
            'critical' => 0,
            'error' => 0,
            'warning' => 0,
            'notice' => 0,
            'info' => 0,
            'debug' => 0,
        ];
        $visibleLines = collect();
        $totalLines = 0;
        $readError = null;

        if ($selectedFile) {
            try {
                [$visibleLines, $totalLines, $levelCounts] = $this->readLogFile(
                    $selectedFile->getPathname(),
                    $levelCounts
                );
            } catch (Throwable $exception) {
                report($exception);
                $readError = 'The selected log file could not be read. Please check its permissions and try again.';
            }
        }

        return view('admin.logs.index', [
            'logFiles' => $files->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size_kb' => round($file->getSize() / 1024, 2),
                'updated_at' => date('d M Y, h:i A', $file->getMTime()),
            ]),
            'selectedFileName' => $selectedFile?->getFilename(),
            'selectedFileSizeKb' => $selectedFile ? round($selectedFile->getSize() / 1024, 2) : 0,
            'selectedFileUpdatedAt' => $selectedFile ? date('d M Y, h:i A', $selectedFile->getMTime()) : null,
            'visibleLines' => $visibleLines,
            'totalLines' => $totalLines,
            'visibleLineCount' => $visibleLines->count(),
            'levelCounts' => $levelCounts,
            'readError' => $readError,
        ]);
    }

    /**
     * Read the file one line at a time so large production logs do not exhaust PHP memory.
     *
     * @param  array<string, int>  $levelCounts
     * @return array{0: \Illuminate\Support\Collection<int, string>, 1: int, 2: array<string, int>}
     */
    private function readLogFile(string $path, array $levelCounts): array
    {
        $file = new SplFileObject($path, 'rb');
        $buffer = [];
        $totalLines = 0;

        while (!$file->eof()) {
            $line = rtrim((string) $file->fgets(), "\r\n");
            if ($line === '') {
                continue;
            }

            if (!preg_match('//u', $line)) {
                $line = iconv('UTF-8', 'UTF-8//IGNORE', $line) ?: '[invalid log data]';
            }

            $totalLines++;
            $buffer[($totalLines - 1) % self::MAX_VISIBLE_LINES] = $line;
            $lower = Str::lower($line);

            foreach (array_keys($levelCounts) as $level) {
                if (Str::contains($lower, '.'.$level.':')) {
                    $levelCounts[$level]++;
                    break;
                }
            }
        }

        if ($totalLines > self::MAX_VISIBLE_LINES) {
            $start = $totalLines % self::MAX_VISIBLE_LINES;
            $buffer = array_merge(array_slice($buffer, $start), array_slice($buffer, 0, $start));
        }

        return [collect(array_values($buffer)), $totalLines, $levelCounts];
    }
}
