<?php

namespace App\Console\Commands;

use App\Models\AudienceList;
use App\Jobs\FetchAudienceEmailJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchMissingAudienceEmails extends Command
{
    protected $signature = 'audience:fetch-missing-emails 
                            {--audience-id= : Specific audience ID to process}
                            {--limit=50 : Maximum number of items to process}
                            {--batch-size=10 : Number of jobs to dispatch at once}';
    
    protected $description = 'Fetch missing emails for audience list items using PhantomBuster Profile Scraper';

    public function handle()
    {
        $audienceId = $this->option('audience-id');
        $limit = (int) $this->option('limit');
        $batchSize = (int) $this->option('batch-size');

        $this->info("🔍 Finding audience list items without emails...");

        // Build query
        $query = AudienceList::whereNull('con_email')
            ->orWhere('con_email', '')
            ->whereNotNull('con_public_identifier')
            ->where('con_public_identifier', '!=', '');

        if ($audienceId) {
            $query->where('audience_id', $audienceId);
            $this->info("📋 Filtering by audience ID: {$audienceId}");
        }

        $items = $query->limit($limit)->get();

        if ($items->isEmpty()) {
            $this->info("✅ No audience items found without emails.");
            return 0;
        }

        $this->info("📊 Found {$items->count()} items without emails.");
        $this->info("🚀 Dispatching jobs to fetch emails...");

        $dispatched = 0;
        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        foreach ($items->chunk($batchSize) as $chunk) {
            foreach ($chunk as $item) {
                try {
                    FetchAudienceEmailJob::dispatch($item->id, $item->con_public_identifier);
                    
                    $dispatched++;
                    $bar->advance();
                } catch (\Throwable $th) {
                    $this->newLine();
                    $this->error("❌ Failed to dispatch job for item ID {$item->id}: " . $th->getMessage());
                    Log::error('Failed to dispatch FetchAudienceEmailJob', [
                        'audience_list_id' => $item->id,
                        'error' => $th->getMessage()
                    ]);
                }
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Dispatched {$dispatched} jobs to fetch emails.");
        $this->info("💡 Run 'php artisan queue:work' to process the jobs.");

        return 0;
    }
}
