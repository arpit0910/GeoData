<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\ServerLogReader;
use Throwable;

class LogController extends Controller
{
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

        $filters = $request->validate([
            'level' => 'nullable|in:'.implode(',', ServerLogReader::LEVELS).',unknown',
            'q' => 'nullable|string|max:200',
        ]);
        $level = $filters['level'] ?? '';
        $search = trim($filters['q'] ?? '');
        $result = [
            'entries' => [], 'totalEntries' => 0, 'matchingEntries' => 0,
            'levelCounts' => array_fill_keys(ServerLogReader::LEVELS, 0),
        ];
        $readError = null;

        if ($selectedFile) {
            try {
                $result = app(ServerLogReader::class)->read($selectedFile->getPathname(), $level, $search);
            } catch (Throwable $exception) {
                report($exception);
                $readError = 'The selected log file could not be read. Please check its permissions and try again.';
            }
        }

        return view('admin.logs.index', array_merge($result, [
            'logFiles' => $files->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size_kb' => round($file->getSize() / 1024, 2),
                'updated_at' => date('d M Y, h:i A', $file->getMTime()),
            ]),
            'selectedFileName' => $selectedFile?->getFilename(),
            'selectedFileSizeKb' => $selectedFile ? round($selectedFile->getSize() / 1024, 2) : 0,
            'selectedFileUpdatedAt' => $selectedFile ? date('d M Y, h:i A', $selectedFile->getMTime()) : null,
            'level' => $level,
            'search' => $search,
            'readError' => $readError,
        ]));
    }

}
