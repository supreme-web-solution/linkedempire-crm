<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PhantomBusterWorkspaceManager
{
    private array $workspaces = [];
    private int $workspaceCount = 0;
    private int $lockTimeout;

    public function __construct()
    {
        $apiKeys = config('services.phantombuster.workspace_api_keys', []);
        $this->lockTimeout = config('services.phantombuster.workspace_lock_timeout', 300);
        
        // Build workspace array with API keys and phantom IDs
        foreach ($apiKeys as $index => $apiKey) {
            if (empty($apiKey)) continue;
            
            $this->workspaces[] = [
                'index' => $index,
                'api_key' => $apiKey,
                'linkedin_post_likers_phantom_id' => $this->getPhantomId('workspace_linkedin_post_likers_phantom_ids', $index),
                'linkedin_post_comments_phantom_id' => $this->getPhantomId('workspace_linkedin_post_comments_phantom_ids', $index),
                'linkedin_search_export_phantom_id' => $this->getPhantomId('workspace_linkedin_search_export_phantom_ids', $index),
                'linkedin_profile_scraper_phantom_id' => $this->getPhantomId('workspace_linkedin_profile_scraper_phantom_ids', $index),
            ];
        }
        
        $this->workspaceCount = count($this->workspaces);
        
        // Fallback to single workspace if no multi-workspace configured
        if ($this->workspaceCount === 0) {
            $singleApiKey = config('services.phantombuster.api_key');
            if ($singleApiKey) {
                $this->workspaces[] = [
                    'index' => 0,
                    'api_key' => $singleApiKey,
                    'linkedin_post_likers_phantom_id' => config('services.phantombuster.linkedin_post_likers_phantom_id'),
                    'linkedin_post_comments_phantom_id' => config('services.phantombuster.linkedin_post_comments_phantom_id'),
                    'linkedin_search_export_phantom_id' => config('services.phantombuster.linkedin_search_export_phantom_id'),
                    'linkedin_profile_scraper_phantom_id' => config('services.phantombuster.linkedin_profile_scraper_phantom_id'),
                ];
                $this->workspaceCount = 1;
            }
        }
    }

    /**
     * Get phantom ID for a specific workspace index
     */
    private function getPhantomId(string $configKey, int $index): ?string
    {
        $phantomIds = config("services.phantombuster.{$configKey}", []);
        if (is_array($phantomIds) && isset($phantomIds[$index])) {
            return $phantomIds[$index];
        }
        return null;
    }

    /**
     * Acquire an available workspace
     * Returns workspace data or null if all are busy and timeout reached
     * 
     * @param int $maxWaitSeconds Maximum seconds to wait for available workspace
     * @return array|null Workspace data or null if timeout
     */
    public function acquireWorkspace(int $maxWaitSeconds = null): ?array
    {
        $maxWaitSeconds = $maxWaitSeconds ?? $this->lockTimeout;
        $startTime = time();
        
        while (time() - $startTime < $maxWaitSeconds) {
            // Try each workspace in round-robin fashion
            foreach ($this->workspaces as $workspace) {
                $lockKey = "phantombuster_workspace_{$workspace['index']}";
                $lock = Cache::lock($lockKey, 1800); // 30 minutes max lock duration
                
                if ($lock->get()) {
                    Log::info('🔓 PhantomBuster: Acquired workspace', [
                        'workspace_index' => $workspace['index'],
                        'wait_time_seconds' => time() - $startTime
                    ]);
                    
                    return [
                        'workspace' => $workspace,
                        'lock' => $lock,
                        'lock_key' => $lockKey
                    ];
                }
            }
            
            // All workspaces busy, wait a bit before retrying
            sleep(2);
        }
        
        Log::error('❌ PhantomBuster: All workspaces busy, timeout reached', [
            'workspace_count' => $this->workspaceCount,
            'wait_time_seconds' => time() - $startTime,
            'max_wait_seconds' => $maxWaitSeconds
        ]);
        
        return null;
    }

    /**
     * Release a workspace lock
     */
    public function releaseWorkspace($lock): void
    {
        if ($lock) {
            try {
                $lock->release();
                Log::info('🔓 PhantomBuster: Released workspace lock');
            } catch (\Exception $e) {
                Log::warning('⚠️ PhantomBuster: Failed to release workspace lock', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Get workspace count
     */
    public function getWorkspaceCount(): int
    {
        return $this->workspaceCount;
    }

    /**
     * Check if multi-workspace is enabled
     */
    public function isMultiWorkspaceEnabled(): bool
    {
        return $this->workspaceCount > 1;
    }
}
