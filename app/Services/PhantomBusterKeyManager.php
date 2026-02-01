<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Manages multiple PhantomBuster API keys (workspaces) with rotation and locking
 * 
 * Allows concurrent processing by using different keys when one is busy.
 * Each key can only handle one operation at a time (PhantomBuster limitation).
 */
class PhantomBusterKeyManager
{
    private array $apiKeys;
    private array $phantomIds;
    private int $lockTimeout;
    private int $lockDuration;
    
    public function __construct()
    {
        // Parse comma-separated API keys from env
        $apiKeysStr = config('services.phantombuster.api_key', '');
        $this->apiKeys = array_filter(array_map('trim', explode(',', $apiKeysStr)));
        
        if (empty($this->apiKeys)) {
            Log::error('PhantomBusterKeyManager: No API keys configured');
            throw new \Exception('PHANTOMBUSTER_API_KEY not configured');
        }
        
        // Parse comma-separated phantom IDs for each phantom type
        $this->phantomIds = [
            'post_likers' => $this->parseConfigArray('services.phantombuster.linkedin_post_likers_phantom_id'),
            'post_comments' => $this->parseConfigArray('services.phantombuster.linkedin_post_comments_phantom_id'),
            'profile_scraper' => $this->parseConfigArray('services.phantombuster.linkedin_profile_scraper_phantom_id'),
            'search_export' => $this->parseConfigArray('services.phantombuster.linkedin_search_export_phantom_id'),
        ];
        
        $this->lockTimeout = 300; // 5 minutes - max wait for a key
        $this->lockDuration = 900; // 15 minutes - lock duration (safety net)
        
        Log::info('🔑 PhantomBusterKeyManager: Initialized', [
            'total_keys' => count($this->apiKeys),
            'keys_count' => count($this->apiKeys),
            'post_likers_phantoms' => count($this->phantomIds['post_likers']),
            'profile_scraper_phantoms' => count($this->phantomIds['profile_scraper']),
        ]);
    }
    
    /**
     * Parse comma-separated config value into array
     */
    private function parseConfigArray(string $configKey): array
    {
        $value = config($configKey, '');
        if (empty($value)) {
            return [];
        }
        return array_filter(array_map('trim', explode(',', $value)));
    }
    
    /**
     * Get an available key-phantom pair for a specific operation type
     * 
     * @param string $operationType One of: 'post_likers', 'post_comments', 'profile_scraper', 'search_export'
     * @return array ['api_key' => string, 'phantom_id' => string, 'key_index' => int, 'lock' => Lock]
     * @throws \Exception If no key is available after timeout
     */
    public function acquireKeyPair(string $operationType): array
    {
        $phantomIds = $this->phantomIds[$operationType] ?? [];
        
        if (empty($phantomIds)) {
            throw new \Exception("No phantom IDs configured for operation type: {$operationType}");
        }
        
        // Ensure we have matching counts (use first phantom if fewer phantoms than keys)
        $maxPairs = min(count($this->apiKeys), count($phantomIds));
        
        Log::info('🔍 PhantomBusterKeyManager: Attempting to acquire key pair', [
            'operation_type' => $operationType,
            'available_keys' => count($this->apiKeys),
            'available_phantoms' => count($phantomIds),
            'max_pairs' => $maxPairs
        ]);
        
        $startTime = time();
        $attempts = 0;
        
        // Try each key-phantom pair in rotation
        while (time() - $startTime < $this->lockTimeout) {
            for ($i = 0; $i < $maxPairs; $i++) {
                $attempts++;
                $apiKey = $this->apiKeys[$i];
                $phantomId = $phantomIds[$i];
                $keyIndex = $i;
                
                // Lock key is per key (not per phantom) since each key can only run one operation
                $lockKey = "phantombuster:key:{$keyIndex}:lock";
                $lock = Cache::lock($lockKey, $this->lockDuration);
                
                // Try to acquire lock (non-blocking first attempt)
                $acquired = $lock->get();
                
                if ($acquired) {
                    Log::info('✅ PhantomBusterKeyManager: Acquired key pair', [
                        'operation_type' => $operationType,
                        'key_index' => $keyIndex,
                        'phantom_id' => $phantomId,
                        'attempts' => $attempts,
                        'wait_time_seconds' => time() - $startTime,
                        'lock_key' => $lockKey
                    ]);
                    
                    return [
                        'api_key' => $apiKey,
                        'phantom_id' => $phantomId,
                        'key_index' => $keyIndex,
                        'lock' => $lock,
                        'lock_key' => $lockKey
                    ];
                }
                
                // Log which key is busy
                if ($attempts <= $maxPairs) {
                    Log::info('⏳ PhantomBusterKeyManager: Key is busy, trying next', [
                        'operation_type' => $operationType,
                        'key_index' => $keyIndex,
                        'phantom_id' => $phantomId,
                        'lock_key' => $lockKey,
                        'attempt' => $attempts
                    ]);
                }
            }
            
            // All keys are busy, wait a bit before retrying
            if (time() - $startTime < $this->lockTimeout) {
                $waitTime = min(5, ($this->lockTimeout - (time() - $startTime)) / 10); // Wait up to 5 seconds
                if ($waitTime > 0) {
                    Log::info('⏸️ PhantomBusterKeyManager: All keys busy, waiting before retry', [
                        'operation_type' => $operationType,
                        'wait_seconds' => $waitTime,
                        'elapsed_seconds' => time() - $startTime,
                        'max_wait_seconds' => $this->lockTimeout
                    ]);
                    sleep((int)$waitTime);
                }
            }
        }
        
        // Timeout - all keys are busy
        Log::error('❌ PhantomBusterKeyManager: Failed to acquire key pair - all keys busy', [
            'operation_type' => $operationType,
            'total_keys' => count($this->apiKeys),
            'total_phantoms' => count($phantomIds),
            'timeout_seconds' => $this->lockTimeout,
            'total_attempts' => $attempts
        ]);
        
        throw new \Exception(
            "LOCK_TIMEOUT: All PhantomBuster keys are currently in use. " .
            "Tried {$attempts} times over {$this->lockTimeout} seconds. " .
            "Please wait for current operations to complete or add more API keys."
        );
    }
    
    /**
     * Release a key lock
     */
    public function releaseKeyPair(array $keyPair): void
    {
        if (isset($keyPair['lock']) && $keyPair['lock']) {
            try {
                $keyPair['lock']->release();
                Log::info('🔓 PhantomBusterKeyManager: Released key pair', [
                    'key_index' => $keyPair['key_index'] ?? 'unknown',
                    'phantom_id' => $keyPair['phantom_id'] ?? 'unknown',
                    'lock_key' => $keyPair['lock_key'] ?? 'unknown'
                ]);
            } catch (\Exception $e) {
                Log::warning('⚠️ PhantomBusterKeyManager: Failed to release lock', [
                    'key_index' => $keyPair['key_index'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Get status of all keys (for debugging)
     */
    public function getKeysStatus(): array
    {
        $status = [];
        foreach ($this->apiKeys as $index => $key) {
            $lockKey = "phantombuster:key:{$index}:lock";
            $lock = Cache::lock($lockKey, $this->lockDuration);
            $status[] = [
                'key_index' => $index,
                'is_locked' => !$lock->get(),
                'lock_key' => $lockKey
            ];
        }
        return $status;
    }
}
