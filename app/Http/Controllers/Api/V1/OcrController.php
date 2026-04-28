<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OcrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OcrController extends Controller
{
    private OcrService $ocr;

    public function __construct(OcrService $ocr)
    {
        $this->ocr = $ocr;
    }

    /**
     * POST /api/v1/ocr/extract
     *
     * Accepts a document image and returns structured OCR data.
     *
     * Form fields:
     *   image         (file, required)  — JPEG / PNG / WEBP / BMP / TIFF
     *   document_type (string, optional) — pan | aadhaar_front | aadhaar_back
     */
    public function extract(Request $request): JsonResponse
    {
        $request->validate([
            'image'         => 'required|file|mimes:jpeg,jpg,png,webp,bmp,tiff|max:10240',
            'document_type' => 'nullable|string|in:pan,aadhaar_front,aadhaar_back,voter_id,driving_license,passport',
        ]);

        $result = $this->ocr->extract(
            $request->file('image'),
            $request->input('document_type')
        );

        // Propagate non-200 errors from the microservice
        if (isset($result['success']) && $result['success'] === false) {
            $statusCode = $result['code']
                ?? (isset($result['validation']) ? 422 : 500);

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? $result['message'] ?? 'OCR extraction failed.',
                'data' => $result['data'] ?? $result,
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Values extracted successfully.',
            'data'    => $result,
        ]);
    }

    /**
     * GET /api/v1/ocr/health
     *
     * Returns whether the OCR microservice is reachable.
     */
    public function health(): JsonResponse
    {
        $alive = $this->ocr->ping();

        return response()->json([
            'success' => $alive,
            'status'  => $alive ? 'OCR service is online.' : 'OCR service is unreachable.',
        ], $alive ? 200 : 503);
    }
}
