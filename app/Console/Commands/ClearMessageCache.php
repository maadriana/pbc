<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearMessageCache extends Command
{
    protected $signature = 'pbc:clear-message-cache {--user= : Clear cache for specific user}';
    protected $description = 'Clear message-related cache data';

    public function handle()
    {
        if ($userId = $this->option('user')) {
            Cache::forget("user_conversations_{$userId}");
            Cache::forget("user_unread_count_{$userId}");
            $this->info("Cleared message cache for user {$userId}");
        } else {
            Cache::flush();
            $this->info('Cleared all message cache');
        }
    }
}
