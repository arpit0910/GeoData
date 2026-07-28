<?php

namespace App\Services;

use App\Models\ApiTestReport;

class ApiTestReportExporter
{
    private const PAGE_WIDTH = 612;
    private const PAGE_HEIGHT = 842;
    private const LEFT_MARGIN = 40;
    private const CONTENT_WIDTH = 532;
    private const FOOTER_Y = 24;
    private const SUMMARY_START_Y = 456;
    private const CONTINUED_START_Y = 682;
    private const BOTTOM_LIMIT_Y = 48;

    public function buildPayload(ApiTestReport $report): array
    {
        return [
            'id' => $report->id,
            'name' => $report->report_name,
            'mode' => $report->mode,
            'status' => $report->status,
            'company' => [
                'id' => $report->targetUser?->id,
                'name' => $report->targetUser?->company_name ?: $report->targetUser?->name,
                'email' => $report->targetUser?->email,
                'client_key' => $report->targetUser?->client_key,
            ],
            'generated_by' => [
                'id' => $report->generatedBy?->id,
                'name' => $report->generatedBy?->name,
                'email' => $report->generatedBy?->email,
            ],
            'summary' => $report->summary,
            'results' => $report->results,
            'started_at' => optional($report->started_at)->toDateTimeString(),
            'completed_at' => optional($report->completed_at)->toDateTimeString(),
            'generated_at' => optional($report->created_at)->toDateTimeString(),
        ];
    }

    public function buildPdf(ApiTestReport $report): string
    {
        return $this->renderStyledPdf($this->buildPayload($report));
    }

    private function renderStyledPdf(array $payload): string
    {
        $pages = $this->paginateResults($payload);
        $objects = [];
        $fontRegularId = 3;
        $fontBoldId = 4;
        $fontMonoId = 5;
        $nextObjectId = 6;
        $pageObjectIds = [];

        foreach ($pages as $pageIndex => $pageData) {
            $pageObjectId = $nextObjectId++;
            $contentObjectId = $nextObjectId++;
            $pageObjectIds[] = [$pageObjectId, $contentObjectId, $pageIndex, $pageData];
        }

        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $kids = implode(' ', array_map(fn ($page) => $page[0] . ' 0 R', $pageObjectIds));
        $objects[2] = "<< /Type /Pages /Kids [ {$kids} ] /Count " . count($pageObjectIds) . " >>";
        $objects[$fontRegularId] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[$fontBoldId] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";
        $objects[$fontMonoId] = "<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>";

        foreach ($pageObjectIds as [$pageObjectId, $contentObjectId, $pageIndex, $pageData]) {
            $stream = $this->buildPageStream($payload, $pageData, $pageIndex + 1, count($pageObjectIds));
            $objects[$pageObjectId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 " . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . "] /Resources << /Font << /F1 {$fontRegularId} 0 R /F2 {$fontBoldId} 0 R /F3 {$fontMonoId} 0 R >> >> /Contents {$contentObjectId} 0 R >>";
            $objects[$contentObjectId] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $maxObjectId = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxObjectId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= $maxObjectId; $i++) {
            $offset = $offsets[$i] ?? 0;
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $safeTitle = $this->escapePdfText((string) ($payload['name'] ?? 'API Report'));
        $pdf .= "trailer\n<< /Size " . ($maxObjectId + 1) . " /Root 1 0 R /Info << /Title ({$safeTitle}) >> >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function paginateResults(array $payload): array
    {
        $segments = $this->expandResultsForPdf(collect($payload['results'] ?? [])->values()->all());
        $pages = [];
        $page = [
            'summary' => true,
            'results' => [],
        ];
        $availableHeight = self::SUMMARY_START_Y - self::BOTTOM_LIMIT_Y;

        foreach ($segments as $segment) {
            $blockHeight = $this->estimateResultBlockHeight($segment);

            if ($blockHeight > $availableHeight && $page['results'] !== []) {
                $pages[] = $page;
                $page = [
                    'summary' => false,
                    'results' => [],
                ];
                $availableHeight = self::CONTINUED_START_Y - self::BOTTOM_LIMIT_Y;
            }

            $page['results'][] = $segment;
            $availableHeight -= $blockHeight;
        }

        if ($page['summary'] || $page['results'] !== [] || $pages === []) {
            $pages[] = $page;
        }

        return $pages;
    }

    private function expandResultsForPdf(array $results): array
    {
        $expanded = [];

        foreach ($results as $index => $result) {
            $route = ($result['method'] ?? 'GET') . ' ' . ($result['tested_uri'] ?? ($result['uri'] ?? 'N/A'));
            $routeLines = $this->wrapText($route, 72);
            $requestLines = $this->buildJsonBlockLines('Request', $result['request_params'] ?? [], 88);
            $responseLines = $this->buildJsonBlockLines('Response', $this->normalizeResponsePreview($result['response_preview'] ?? ''), 88);
            $maxBodyLines = max(8, (int) floor((600 - 66 - (count($routeLines) * 13)) / 10));

            $requestOffset = 0;
            $responseOffset = 0;
            $segmentIndex = 0;

            while ($segmentIndex === 0 || $requestOffset < count($requestLines) || $responseOffset < count($responseLines)) {
                $remainingLines = $maxBodyLines;
                $requestChunk = [];
                $responseChunk = [];

                if ($requestOffset < count($requestLines)) {
                    $requestChunk = array_slice($requestLines, $requestOffset, $remainingLines);
                    $requestOffset += count($requestChunk);
                    $remainingLines -= count($requestChunk);
                }

                if ($remainingLines > 0 && $responseOffset < count($responseLines)) {
                    $responseChunk = array_slice($responseLines, $responseOffset, $remainingLines);
                    $responseOffset += count($responseChunk);
                }

                if ($segmentIndex > 0 && $requestChunk !== []) {
                    $requestChunk[0] = 'Request (cont.):';
                }

                if ($segmentIndex > 0 && $responseChunk !== []) {
                    $responseChunk[0] = 'Response (cont.):';
                }

                $expanded[] = array_merge($result, [
                    'pdf_position' => $index + 1,
                    'pdf_route_lines' => $routeLines,
                    'pdf_request_lines' => $requestChunk,
                    'pdf_response_lines' => $responseChunk,
                    'pdf_continued' => $segmentIndex > 0,
                ]);

                $segmentIndex++;
            }
        }

        return $expanded;
    }

    private function estimateResultBlockHeight(array $result): int
    {
        $routeLines = $result['pdf_route_lines'] ?? $this->wrapText(($result['method'] ?? 'GET') . ' ' . ($result['tested_uri'] ?? ($result['uri'] ?? 'N/A')), 72);
        $requestLines = $result['pdf_request_lines'] ?? $this->buildJsonBlockLines('Request', $result['request_params'] ?? [], 88);
        $responseLines = $result['pdf_response_lines'] ?? $this->buildJsonBlockLines('Response', $this->normalizeResponsePreview($result['response_preview'] ?? ''), 88);

        return 66 + (count($routeLines) * 13) + (count($requestLines) * 10) + (count($responseLines) * 10);
    }

    private function buildPageStream(array $payload, array $pageData, int $pageNumber, int $totalPages): string
    {
        $ops = [];
        $ops[] = '0.98 0.99 1 rg 0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . ' re f';
        $ops[] = '0.08 0.36 0.50 rg 0 754 ' . self::PAGE_WIDTH . ' 88 re f';
        $ops[] = $this->drawText(self::LEFT_MARGIN, 804, 'SetuGeo API Health Report', 'F2', 22, [1, 1, 1]);
        $ops[] = $this->drawText(self::LEFT_MARGIN, 784, (string) ($payload['name'] ?? 'API Report'), 'F1', 11, [0.88, 0.96, 1]);
        $ops[] = $this->drawText(490, 804, 'Page ' . $pageNumber . ' / ' . $totalPages, 'F1', 10, [0.88, 0.96, 1]);

        if ($pageData['summary']) {
            $ops = array_merge($ops, $this->buildSummarySection($payload));
            $y = self::SUMMARY_START_Y;
        } else {
            $ops[] = $this->drawSectionHeading(self::LEFT_MARGIN, 726, 'Endpoint Results');
            $ops[] = $this->drawText(self::LEFT_MARGIN, 708, 'Continued report results for the selected company context.', 'F1', 9, [0.38, 0.44, 0.50]);
            $y = self::CONTINUED_START_Y;
        }

        foreach ($pageData['results'] as $result) {
            [$cardOps, $nextY] = $this->drawResultCard($result, $y, (int) ($result['pdf_position'] ?? 1));
            $ops = array_merge($ops, $cardOps);
            $y = $nextY;
        }

        $ops[] = $this->drawText(self::LEFT_MARGIN, self::FOOTER_Y, 'PDF includes full request and response data across as many pages as needed.', 'F1', 8, [0.42, 0.46, 0.50]);

        return implode("\n", array_filter($ops));
    }

    private function buildSummarySection(array $payload): array
    {
        $ops = [];
        $companyName = (string) ($payload['company']['name'] ?? 'N/A');
        $companyEmail = (string) ($payload['company']['email'] ?? 'N/A');
        $clientKey = (string) ($payload['company']['client_key'] ?? 'N/A');
        $generatedAt = (string) ($payload['generated_at'] ?? 'N/A');
        $mode = strtoupper((string) ($payload['mode'] ?? 'N/A'));
        $status = strtoupper((string) ($payload['status'] ?? 'N/A'));
        $summary = $payload['summary'] ?? [];

        $ops[] = $this->drawSectionHeading(self::LEFT_MARGIN, 726, 'Company Context');
        $ops[] = $this->drawInfoCard(self::LEFT_MARGIN, 620, self::CONTENT_WIDTH, 88);
        $ops = array_merge($ops, $this->drawWrappedLabelValue(56, 692, 214, 'Company', $companyName));
        $ops = array_merge($ops, $this->drawWrappedLabelValue(56, 656, 214, 'Email', $companyEmail));
        $ops = array_merge($ops, $this->drawWrappedLabelValue(320, 692, 214, 'Client Key', $clientKey));
        $ops = array_merge($ops, $this->drawWrappedLabelValue(320, 656, 214, 'Mode / Status', $mode . ' / ' . $status));
        $ops[] = $this->drawText(56, 632, 'Generated: ' . $generatedAt, 'F1', 8, [0.40, 0.45, 0.50]);

        $ops[] = $this->drawSectionHeading(self::LEFT_MARGIN, 592, 'Summary');
        $cards = [
            ['Total Endpoints', (string) ($summary['total'] ?? 0), [0.90, 0.95, 0.99]],
            ['Passed', (string) ($summary['passed'] ?? 0), [0.90, 0.97, 0.92]],
            ['Failed', (string) ($summary['failed'] ?? 0), [0.99, 0.92, 0.92]],
            ['Skipped', (string) ($summary['skipped'] ?? 0), [0.95, 0.94, 0.99]],
            ['Avg Duration', (string) ($summary['average_duration_ms'] ?? 0) . ' ms', [0.99, 0.96, 0.89]],
        ];

        foreach ($cards as $index => [$label, $value, $fill]) {
            $x = self::LEFT_MARGIN + ($index * 106);
            $ops[] = sprintf('%.2F %.2F %.2F rg %d 514 96 58 re f', $fill[0], $fill[1], $fill[2], $x);
            $ops[] = '0.84 0.89 0.94 RG ' . $x . ' 514 96 58 re S';
            $ops[] = $this->drawText($x + 12, 552, $label, 'F1', 9, [0.34, 0.40, 0.46]);
            $ops[] = $this->drawText($x + 12, 528, $value, 'F2', 18, [0.08, 0.14, 0.20]);
        }

        $ops[] = $this->drawSectionHeading(self::LEFT_MARGIN, 484, 'Endpoint Results');

        return $ops;
    }

    private function drawResultCard(array $result, int $y, int $position): array
    {
        $ops = [];
        $routeLines = $result['pdf_route_lines'] ?? $this->wrapText(($result['method'] ?? 'GET') . ' ' . ($result['tested_uri'] ?? ($result['uri'] ?? 'N/A')), 72);
        $requestLines = $result['pdf_request_lines'] ?? $this->buildJsonBlockLines('Request', $result['request_params'] ?? [], 88);
        $responseLines = $result['pdf_response_lines'] ?? $this->buildJsonBlockLines('Response', $this->normalizeResponsePreview($result['response_preview'] ?? ''), 88);
        $cardHeight = $this->estimateResultBlockHeight($result);
        $bottomY = $y - $cardHeight;

        $ops[] = sprintf('1 1 1 rg %d %d %d %d re f', self::LEFT_MARGIN, $bottomY, self::CONTENT_WIDTH, $cardHeight);
        $ops[] = sprintf('0.86 0.89 0.93 RG %d %d %d %d re S', self::LEFT_MARGIN, $bottomY, self::CONTENT_WIDTH, $cardHeight);

        $statusColor = ($result['ok'] ?? false) ? [0.14, 0.55, 0.23] : [0.74, 0.19, 0.18];
        $statusLabel = 'HTTP ' . ($result['status'] ?? 'n/a') . (! empty($result['pdf_continued']) ? ' (cont.)' : '');
        $categoryLine = 'Category: ' . ($result['category'] ?? 'N/A') . ' | Duration: ' . ($result['duration_ms'] ?? 0) . ' ms';
        $textY = $y - 20;

        foreach ($routeLines as $lineIndex => $line) {
            $label = $lineIndex === 0 ? $position . '. ' . $line : '   ' . $line;
            $ops[] = $this->drawText(54, $textY - ($lineIndex * 13), $label, 'F2', 10, [0.08, 0.14, 0.20]);
        }

        $ops[] = $this->drawText(438, $y - 20, $statusLabel, 'F2', 10, $statusColor);
        $ops[] = $this->drawText(54, $textY - (count($routeLines) * 13) - 4, $categoryLine, 'F1', 9, [0.32, 0.38, 0.44]);

        $requestY = $textY - (count($routeLines) * 13) - 20;
        foreach ($requestLines as $lineIndex => $line) {
            $ops[] = $this->drawText(54, $requestY - ($lineIndex * 10), $line, 'F3', 8, [0.23, 0.32, 0.40]);
        }

        $responseY = $requestY - (count($requestLines) * 10) - 8;
        foreach ($responseLines as $lineIndex => $line) {
            $ops[] = $this->drawText(54, $responseY - ($lineIndex * 10), $line, 'F3', 8, [0.35, 0.40, 0.46]);
        }

        return [$ops, $bottomY - 14];
    }

    private function normalizeResponsePreview(mixed $preview): mixed
    {
        if (is_array($preview)) {
            if (array_key_exists('preview', $preview) && is_string($preview['preview'])) {
                $nestedDecoded = json_decode($preview['preview'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $nestedDecoded;
                }
            }

            return $preview;
        }

        $preview = trim((string) $preview);

        if ($preview === '') {
            return ['message' => 'No response body returned.'];
        }

        $decoded = json_decode($preview, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (is_array($decoded) && array_key_exists('preview', $decoded) && is_string($decoded['preview'])) {
                $nestedDecoded = json_decode($decoded['preview'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $nestedDecoded;
                }
            }

            return $decoded;
        }

        return ['preview' => preg_replace('/\s+/', ' ', $preview) ?? $preview];
    }

    private function buildJsonBlockLines(string $label, mixed $value, int $wrapLength): array
    {
        $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $json = $json === false ? (string) $value : $json;
        $lines = explode("\n", $json);
        $wrapped = [$label . ':'];

        foreach ($lines as $line) {
            $line = rtrim($line);
            $wrapped = array_merge($wrapped, explode("\n", wordwrap($line === '' ? ' ' : $line, $wrapLength, "\n", true)));
        }

        return $wrapped;
    }

    private function drawSectionHeading(int $x, int $y, string $title): string
    {
        return $this->drawText($x, $y, $title, 'F2', 13, [0.08, 0.14, 0.20]);
    }

    private function drawInfoCard(int $x, int $y, int $width, int $height): string
    {
        return sprintf('0.98 0.99 1 rg %d %d %d %d re f 0.86 0.89 0.93 RG %d %d %d %d re S', $x, $y, $width, $height, $x, $y, $width, $height);
    }

    private function drawWrappedLabelValue(int $x, int $y, int $width, string $label, string $value): array
    {
        $ops = [];
        $ops[] = $this->drawText($x, $y, $label, 'F1', 8, [0.40, 0.45, 0.50]);

        foreach ($this->wrapText($value, max(18, (int) floor($width / 7))) as $lineIndex => $line) {
            $ops[] = $this->drawText($x, $y - 12 - ($lineIndex * 11), $line, 'F2', 10, [0.08, 0.14, 0.20]);
        }

        return $ops;
    }

    private function drawText(int $x, int $y, string $text, string $font, int $size, array $rgb): string
    {
        return sprintf(
            'BT %.2F %.2F %.2F rg /%s %d Tf 1 0 0 1 %d %d Tm (%s) Tj ET',
            $rgb[0],
            $rgb[1],
            $rgb[2],
            $font,
            $size,
            $x,
            $y,
            $this->escapePdfText($text)
        );
    }

    private function wrapText(string $text, int $length = 90): array
    {
        return explode("\n", wordwrap($text, $length, "\n", true));
    }

    private function escapePdfText(string $text): string
    {
        $text = preg_replace('/[^\P{C}\n\t]/u', '', $text) ?? $text;
        $text = str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], $text);

        return preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
    }
}
