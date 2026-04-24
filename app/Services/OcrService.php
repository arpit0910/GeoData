<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\UploadedFile;

class OcrService
{
    private Client $client;
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ocr.url', 'http://localhost:8001'), '/');
        $this->apiKey = (string) config('services.ocr.key', '');
        $this->client  = new Client([
            'timeout'         => (int) config('services.ocr.timeout', 30),
            'connect_timeout' => (int) config('services.ocr.connect_timeout', 5),
        ]);
    }

    /**
     * Send an image to the OCR microservice and return the structured result.
     *
     * @param  UploadedFile  $image
     * @param  string|null   $documentType  pan | aadhaar_front | aadhaar_back | null (auto-detect)
     * @return array
     */
    public function extract(UploadedFile $image, ?string $documentType = null): array
    {
        try {
            $multipart = [
                [
                    'name'     => 'image',
                    'contents' => fopen($image->getRealPath(), 'r'),
                    'filename' => $image->getClientOriginalName(),
                    'headers'  => ['Content-Type' => $image->getMimeType()],
                ],
            ];

            if ($documentType) {
                $multipart[] = [
                    'name'     => 'document_type',
                    'contents' => $documentType,
                ];
            }

            $response = $this->client->post("{$this->baseUrl}/ocr/extract", [
                'headers' => [
                    'X-API-KEY' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'multipart' => $multipart,
            ]);

            return json_decode($response->getBody()->getContents(), true);

        } catch (ConnectException $e) {
            return $this->serviceUnavailable();
        } catch (RequestException $e) {
            $body = $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : null;

            return [
                'success' => false,
                'error'   => $body['detail'] ?? 'OCR service returned an error.',
                'code'    => $e->getResponse()?->getStatusCode() ?? 500,
            ];
        }
    }

    /**
     * Check if the OCR microservice is reachable.
     */
    public function ping(): bool
    {
        try {
            $response = $this->client->get("{$this->baseUrl}/health", [
                'headers' => ['Accept' => 'application/json'],
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Throwable) {
            return false;
        }
    }

    private function serviceUnavailable(): array
    {
        return [
            'success' => false,
            'error'   => 'OCR service is unreachable. Please try again later.',
            'code'    => 503,
        ];
    }
}
