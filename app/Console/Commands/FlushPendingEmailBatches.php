<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Jobs\FetchAudienceEmailBatchJob;

class FlushPendingEmailBatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:flush-pending-batches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush pending email fetch batches that have been waiting (process batches with less than 20 items after 5 minutes)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Checking for pending email fetch batches...');
        
        // Find all cache keys matching the pattern
        // Note: This requires Redis or a cache driver that supports pattern matching
        // For database/file cache, we'll need a different approach
        
        try {
            // Get all cache keys (this works with Redis)
            // For other cache drivers, we might need to track batch keys in a database table
            $pattern = 'email_fetch_batch:audience:*:user:*';
            
            // Since Laravel cache doesn't have a built-in way to list keys,
            // we'll use a workaround: track batch keys in cache with a list
            $batchTrackerKey = 'email_fetch_batch_tracker';
            $batchKeys = Cache::get($batchTrackerKey, []);
            
            $flushedCount = 0;
            $processedCount = 0;
            
            foreach ($batchKeys as $cacheKey => $metadata) {
                $createdAt = $metadata['created_at'] ?? null;
                $audienceId = $metadata['audience_id'] ?? null;
                $userId = $metadata['user_id'] ?? null;
                
                if (!$createdAt || !$audienceId || !$userId) {
                    continue;
                }
                
                // Check if batch is older than 5 minutes
                $ageInMinutes = now()->diffInMinutes(\Carbon\Carbon::parse($createdAt));
                
                if ($ageInMinutes >= 5) {
                    // Get batch items
                    $batchItems = Cache::get($cacheKey, []);
                    
                    if (!empty($batchItems) && count($batchItems) > 0) {
                        // Extract audience list IDs
                        $audienceListIds = array_column($batchItems, 'audienceListItemId');
                        
                        // Clear the cache
                        Cache::forget($cacheKey);
                        
                        // Remove from tracker
                        unset($batchKeys[$cacheKey]);
                        Cache::put($batchTrackerKey, $batchKeys, 3600); // 1 hour
                        
                        // Dispatch batch job
                        try {
                            FetchAudienceEmailBatchJob::dispatchChunked($audienceListIds, $userId);
                            
                            $flushedCount++;
                            $processedCount += count($audienceListIds);
                            
                            Log::info('Flushed pending email fetch batch', [
                                'cache_key' => $cacheKey,
                                'audience_id' => $audienceId,
                                'user_id' => $userId,
                                'batch_size' => count($audienceListIds),
                                'age_minutes' => $ageInMinutes
                            ]);
                            
                            $this->info("  ✅ Flushed batch: {$audienceId} (user: {$userId}, items: " . count($audienceListIds) . ")");
                        } catch (\Throwable $th) {
                            Log::error('Failed to flush batch', [
                                'cache_key' => $cacheKey,
                                'error' => $th->getMessage()
                            ]);
                        }
                    } else {
                        // Remove empty batch from tracker
                        unset($batchKeys[$cacheKey]);
                        Cache::put($batchTrackerKey, $batchKeys, 3600);
                    }
                }
            }
            
            $this->info("✅ Flushed {$flushedCount} batches ({$processedCount} total items)");
            
            return 0;
        } catch (\Throwable $th) {
            $this->error('Error flushing batches: ' . $th->getMessage());
            Log::error('FlushPendingEmailBatches error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            return 1;
        }
    }
}
