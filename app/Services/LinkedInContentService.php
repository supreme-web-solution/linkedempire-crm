<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class LinkedInContentService
{
    protected ?Cloudinary $cloudinary = null;
    protected bool $isConfigured = false;

    public function __construct()
    {
        // Check if Cloudinary is configured before initializing
        $cloudName = config('cloudinary.cloud.cloud_name') ?: env('CLOUDINARY_CLOUD_NAME');
        $apiKey = config('cloudinary.cloud.api_key') ?: env('CLOUDINARY_API_KEY');
        $apiSecret = config('cloudinary.cloud.api_secret') ?: env('CLOUDINARY_API_SECRET');
        
        if ($cloudName && $apiKey && $apiSecret) {
            try {
                $this->cloudinary = new Cloudinary([
                    'cloud' => [
                        'cloud_name' => $cloudName,
                        'api_key' => $apiKey,
                        'api_secret' => $apiSecret,
                    ],
                    'url' => [
                        'secure' => true
                    ]
                ]);
                $this->isConfigured = true;
            } catch (\Exception $e) {
                \Log::warning('Cloudinary initialization failed', ['error' => $e->getMessage()]);
                $this->isConfigured = false;
            }
        } else {
            \Log::warning('Cloudinary not configured - missing credentials');
        }
    }
    
    /**
     * Check if Cloudinary is properly configured
     */
    public function isConfigured(): bool
    {
        return $this->isConfigured && $this->cloudinary !== null;
    }
    
    /**
     * Ensure Cloudinary is configured before operations
     */
    protected function ensureConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Cloudinary is not configured. Please set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, and CLOUDINARY_API_SECRET in your environment.');
        }
    }

    /**
     * Upload a single image for LinkedIn post.
     *
     * @param UploadedFile $file
     * @return string
     */
    public function uploadImage(UploadedFile $file): string
    {
        $this->ensureConfigured();
        
        $filename = $this->generateUniqueFilename($file);
        
        // Upload to Cloudinary
        $cloudinaryResponse = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
            'resource_type' => 'image',
            'folder' => 'linkedin-posts/images',
            'public_id' => pathinfo($filename, PATHINFO_FILENAME),
            'use_filename' => false,
            'unique_filename' => true,
            'transformation' => [
                'quality' => 'auto',
                'fetch_format' => 'auto',
                'width' => 1200,
                'height' => 630,
                'crop' => 'limit'
            ]
        ]);

        return $cloudinaryResponse['secure_url'];
    }

    /**
     * Upload multiple images for LinkedIn carousel post.
     *
     * @param array $files
     * @return array
     */
    public function uploadCarouselImages(array $files): array
    {
        $this->ensureConfigured();
        
        $uploadedUrls = [];
        
        foreach ($files as $file) {
            $filename = $this->generateUniqueFilename($file);
            
            // Upload to Cloudinary
            $cloudinaryResponse = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
                'resource_type' => 'image',
                'folder' => 'linkedin-posts/carousel',
                'public_id' => pathinfo($filename, PATHINFO_FILENAME),
                'use_filename' => false,
                'unique_filename' => true,
                'transformation' => [
                    'quality' => 'auto',
                    'fetch_format' => 'auto',
                    'width' => 1200,
                    'height' => 630,
                    'crop' => 'limit'
                ]
            ]);

            $uploadedUrls[] = $cloudinaryResponse['secure_url'];
        }

        return $uploadedUrls;
    }

    /**
     * Upload a video for LinkedIn post.
     *
     * @param UploadedFile $file
     * @return string
     */
    public function uploadVideo(UploadedFile $file): string
    {
        $this->ensureConfigured();
        
        $filename = $this->generateUniqueFilename($file);
        
        // Upload to Cloudinary
        $cloudinaryResponse = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
            'resource_type' => 'video',
            'folder' => 'linkedin-posts/videos',
            'public_id' => pathinfo($filename, PATHINFO_FILENAME),
            'use_filename' => false,
            'unique_filename' => true,
            'transformation' => [
                'quality' => 'auto',
                'width' => 1280,
                'height' => 720,
                'crop' => 'limit'
            ]
        ]);

        return $cloudinaryResponse['secure_url'];
    }

    /**
     * Upload a document (PDF/PowerPoint) for LinkedIn carousel.
     *
     * @param UploadedFile $file
     * @return string
     */
    public function uploadDocument(UploadedFile $file): string
    {
        $this->ensureConfigured();
        
        $filename = $this->generateUniqueFilename($file);
        
        \Log::info('📄 Uploading carousel document to Cloudinary', [
            'filename' => $filename,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType()
        ]);
        
        // Upload to Cloudinary as raw file
        $cloudinaryResponse = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
            'resource_type' => 'raw', // PDF/PPT are 'raw' resource type
            'folder' => 'linkedin-posts/carousels',
            'public_id' => pathinfo($filename, PATHINFO_FILENAME),
            'use_filename' => false,
            'unique_filename' => true
        ]);

        \Log::info('✅ Document uploaded to Cloudinary', [
            'url' => $cloudinaryResponse['secure_url']
        ]);

        return $cloudinaryResponse['secure_url'];
    }

    /**
     * Generate a unique filename.
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function generateUniqueFilename(UploadedFile $file): string
    {
        return Str::uuid() . '.' . $file->getClientOriginalExtension();
    }

    /**
     * Delete a file from Cloudinary.
     *
     * @param string $publicId
     * @param string $resourceType
     * @return bool
     */
    public function deleteFile(string $publicId, string $resourceType = 'image'): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }
        
        try {
            $this->cloudinary->uploadApi()->destroy($publicId, [
                'resource_type' => $resourceType
            ]);
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to delete Cloudinary file', [
                'public_id' => $publicId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Extract public ID from Cloudinary URL.
     *
     * @param string $url
     * @return string|null
     */
    public function extractPublicId(string $url): ?string
    {
        $pattern = '/\/v\d+\/(.+)\.(jpg|jpeg|png|gif|webp|mp4|mov|avi)$/';
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
