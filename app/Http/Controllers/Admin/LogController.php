<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $logDirectory = storage_path('logs');

        $files = collect(File::exists($logDirectory) ? File::files($logDirectory) : [])
            ->filter(fn ($file) => Str::endsWith($file->getFilename(), '.log'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values();

        $selectedFilename = $request->query('file');
        $selectedFile = $files->first(function ($file) use ($selectedFilename) {
            return $selectedFilename
                ? $file->getFilename() === $selectedFilename
                : true;
        });

        $content = $selectedFile ? File::get($selectedFile->getPathname()) : '';
        $lines = collect(preg_split("/\r\n|\n|\r/", $content ?: ''))
            ->filter(fn ($line) => trim($line) !== '')
            ->values();

        $visibleLines = $lines->take(-1200)->values();

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

        foreach ($lines as $line) {
            $lower = Str::lower($line);

            foreach (array_keys($levelCounts) as $level) {
                if (Str::contains($lower, '.' . $level . ':')) {
                    $levelCounts[$level]++;
                    break;
                }
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
            'totalLines' => $lines->count(),
            'visibleLineCount' => $visibleLines->count(),
            'levelCounts' => $levelCounts,
        ]);
    }
}
