<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ClearPhantomBusterLocks extends Command
{
    protected $signature = 'phantombuster:clear-locks {--key-index= : Clear lock for specific key index (0, 1, 2, etc.)}';
    protected $description = 'Clear stuck PhantomBuster key locks';

    public function handle()
    {
        $keyIndex = $this->option('key-index');
        
        if ($keyIndex !== null) {
            // Clear specific key
            $keyIndex = (int)$keyIndex;
            $lockKey = "phantombuster:key:{$keyIndex}:lock";
            
            $this->info("Clearing lock for key index {$keyIndex}...");
            
            try {
                Cache::forget($lockKey);
                $this->info("✅ Successfully cleared lock for key index {$keyIndex}");
                Log::info('PhantomBuster: Manually cleared lock', [
                    'key_index' => $keyIndex,
                    'lock_key' => $lockKey,
                    'command' => 'phantombuster:clear-locks'
                ]);
            } catch (\Exception $e) {
                $this->error("❌ Failed to clear lock: {$e->getMessage()}");
                return 1;
            }
        } else {
            // Clear all locks (check first 10 keys)
            $this->info("Checking and clearing all PhantomBuster locks...");
            
            $cleared = 0;
            for ($i = 0; $i < 10; $i++) {
                $lockKey = "phantombuster:key:{$i}:lock";
                
                // Check if lock exists
                $lock = Cache::lock($lockKey, 900);
                $isLocked = !$lock->get();
                
                if ($isLocked) {
                    // Lock exists, try to clear it
                    try {
                        Cache::forget($lockKey);
                        $this->info("✅ Cleared lock for key index {$i}");
                        $cleared++;
                        Log::info('PhantomBuster: Manually cleared lock', [
                            'key_index' => $i,
                            'lock_key' => $lockKey,
                            'command' => 'phantombuster:clear-locks'
                        ]);
                    } catch (\Exception $e) {
                        $this->warn("⚠️ Failed to clear lock for key index {$i}: {$e->getMessage()}");
                    }
                } else {
                    // Lock was available, release it (we just checked)
                    $lock->release();
                }
            }
            
            $this->info("Cleared {$cleared} stuck lock(s)");
        }
        
        return 0;
    }
}
