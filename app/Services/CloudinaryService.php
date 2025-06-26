<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CloudinaryService
{
    private $cloudName;
    private $apiKey;
    private $apiSecret;
    private $uploadUrl;

    public function __construct()
    {
        $this->cloudName = config('services.cloudinary.cloud_name');
        $this->apiKey = config('services.cloudinary.api_key');
        $this->apiSecret = config('services.cloudinary.api_secret');
        $this->uploadUrl = "https://api.cloudinary.com/v1_1/{$this->cloudName}/raw/upload";
    }

    /**
     * Upload file to Cloudinary
     */
    public function uploadFile(UploadedFile $file, array $options = []): array
    {
        $publicId = $options['public_id'] ?? 'pbc-documents/' . date('Y/m/') . Str::uuid();
        $folder = $options['folder'] ?? 'pbc-documents';

        // Generate signature
        $timestamp = time();
        $signature = $this->generateSignature([
            'public_id' => $publicId,
            'folder' => $folder,
            'timestamp' => $timestamp,
        ]);

        // Prepare form data
        $formData = [
            'file' => $file,
            'public_id' => $publicId,
            'folder' => $folder,
            'timestamp' => $timestamp,
            'api_key' => $this->apiKey,
            'signature' => $signature,
            'resource_type' => 'raw', // For documents
        ];

        // Add optional tags
        if (isset($options['tags'])) {
            $formData['tags'] = is_array($options['tags']) ? implode(',', $options['tags']) : $options['tags'];
        }

        try {
            $response = Http::asMultipart()->post($this->uploadUrl, $formData);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => true,
                    'public_id' => $result['public_id'],
                    'secure_url' => $result['secure_url'],
                    'url' => $result['url'],
                    'format' => $result['format'],
                    'bytes' => $result['bytes'],
                    'resource_type' => $result['resource_type'],
                    'created_at' => $result['created_at'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Upload failed: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Upload exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete file from Cloudinary
     */
    public function deleteFile(string $publicId): array
    {
        $timestamp = time();
        $signature = $this->generateSignature([
            'public_id' => $publicId,
            'timestamp' => $timestamp,
        ]);

        $destroyUrl = "https://api.cloudinary.com/v1_1/{$this->cloudName}/raw/destroy";

        try {
            $response = Http::asForm()->post($destroyUrl, [
                'public_id' => $publicId,
                'timestamp' => $timestamp,
                'api_key' => $this->apiKey,
                'signature' => $signature,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return [
                    'success' => $result['result'] === 'ok',
                    'result' => $result['result'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Delete failed: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Delete exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get file URL with transformations
     */
    public function getFileUrl(string $publicId, array $transformations = []): string
    {
        $baseUrl = "https://res.cloudinary.com/{$this->cloudName}/raw/upload";

        if (!empty($transformations)) {
            $transformString = $this->buildTransformationString($transformations);
            return "{$baseUrl}/{$transformString}/{$publicId}";
        }

        return "{$baseUrl}/{$publicId}";
    }

    /**
     * Generate download URL
     */
    public function getDownloadUrl(string $publicId, string $filename = null): string
    {
        $transformations = ['fl_attachment'];

        if ($filename) {
            $transformations[] = "fn_{$filename}";
        }

        return $this->getFileUrl($publicId, $transformations);
    }

    /**
     * Generate Cloudinary signature
     */
    private function generateSignature(array $params): string
    {
        ksort($params);
        $stringToSign = '';

        foreach ($params as $key => $value) {
            if ($value !== null && $value !== '') {
                $stringToSign .= "{$key}={$value}&";
            }
        }

        $stringToSign = rtrim($stringToSign, '&') . $this->apiSecret;

        return sha1($stringToSign);
    }

    /**
     * Build transformation string
     */
    private function buildTransformationString(array $transformations): string
    {
        return implode(',', $transformations);
    }

    /**
     * Check if Cloudinary is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->cloudName) && !empty($this->apiKey) && !empty($this->apiSecret);
    }

    /**
     * Get file info from Cloudinary
     */
    public function getFileInfo(string $publicId): array
    {
        $timestamp = time();
        $signature = $this->generateSignature([
            'public_id' => $publicId,
            'timestamp' => $timestamp,
        ]);

        $infoUrl = "https://api.cloudinary.com/v1_1/{$this->cloudName}/resources/raw/{$publicId}";

        try {
            $response = Http::get($infoUrl, [
                'timestamp' => $timestamp,
                'api_key' => $this->apiKey,
                'signature' => $signature,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Info retrieval failed: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Info exception: ' . $e->getMessage(),
            ];
        }
    }
}
