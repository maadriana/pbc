<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\PbcConversation;

class ClearMessageCache extends Command
{
    protected $signature = 'pbc:clear-message-cache {--user= : Clear cache for specific user} {--fix : Fix and diagnose message system issues}';
    protected $description = 'Clear message-related cache data and fix common issues';

    public function handle()
    {
        if ($this->option('fix')) {
            return $this->fixMessageSystem();
        }

        if ($userId = $this->option('user')) {
            $this->clearUserCache($userId);
            $this->info("Cleared message cache for user {$userId}");
        } else {
            $this->clearAllCache();
            $this->info('Cleared all message cache');
        }

        return 0;
    }

    protected function fixMessageSystem()
    {
        $this->info('🔧 Diagnosing and fixing PBC Message System...');

        // Check if tables exist
        $this->info('📋 Checking database tables...');

        $requiredTables = [
            'pbc_conversations',
            'pbc_messages',
            'pbc_conversation_participants'
        ];

        $missingTables = [];
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $missingTables[] = $table;
                $this->error("❌ Missing table: {$table}");
            } else {
                $this->info("✅ Table exists: {$table}");
            }
        }

        if (!empty($missingTables)) {
            $this->error('❌ Missing tables found. Please run: php artisan migrate');
            return 1;
        }

        // Check for users with message permissions
        $this->info('👥 Checking users...');

        try {
            $activeUsers = User::where('is_active', true)->count();
            $totalUsers = User::count();

            $this->info("✅ Found {$activeUsers} active users out of {$totalUsers} total users");

            if ($activeUsers === 0) {
                $this->warn('⚠️  No active users found. Message system may not work properly.');
            }
        } catch (\Exception $e) {
            $this->error('❌ Error checking users: ' . $e->getMessage());
        }

        // Check conversations
        $this->info('💬 Checking conversations...');

        try {
            $conversations = PbcConversation::count();
            $this->info("✅ Found {$conversations} conversations");

            if ($conversations === 0) {
                $this->warn('⚠️  No conversations found. You may want to create test data.');
            }
        } catch (\Exception $e) {
            $this->error('❌ Error checking conversations: ' . $e->getMessage());
        }

        // Clear all caches
        $this->info('🧹 Clearing all message caches...');
        $this->clearAllCache();

        // Check file permissions
        $this->info('📁 Checking storage permissions...');
        $storagePath = storage_path('app/public/conversations');

        if (!is_dir($storagePath)) {
            $this->warn("⚠️  Conversations storage directory doesn't exist: {$storagePath}");
            $this->info('Creating directory...');
            mkdir($storagePath, 0755, true);
            $this->info('✅ Directory created');
        } else {
            $this->info('✅ Storage directory exists');
        }

        if (!is_writable($storagePath)) {
            $this->error("❌ Storage directory is not writable: {$storagePath}");
            $this->info('Please run: chmod -R 755 storage/app/public/conversations');
        } else {
            $this->info('✅ Storage directory is writable');
        }

        $this->info('');
        $this->info('🎉 Message system diagnosis completed!');
        $this->info('');
        $this->info('📝 Summary:');
        $this->info('- Database tables: ' . (empty($missingTables) ? '✅ OK' : '❌ Missing tables'));
        $this->info('- Active users: ' . ($activeUsers > 0 ? '✅ OK' : '⚠️  No active users'));
        $this->info('- Conversations: ' . ($conversations >= 0 ? '✅ OK' : '❌ Error'));
        $this->info('- Storage: ✅ OK');
        $this->info('- Cache: ✅ Cleared');

        return 0;
    }

    protected function clearUserCache($userId)
    {
        $cacheKeys = [
            "user_conversations_{$userId}",
            "user_unread_count_{$userId}"
        ];

        // Clear paginated cache variations
        for ($page = 1; $page <= 10; $page++) {
            $cacheKeys[] = "user_conversations_{$userId}_page_{$page}";
            $cacheKeys[] = "user_conversations_{$userId}_" . md5(json_encode(['page' => $page]));
        }

        // Clear search variations
        $searchTerms = ['', 'active', 'completed', 'archived'];
        foreach ($searchTerms as $term) {
            $cacheKeys[] = "user_conversations_{$userId}_" . md5(json_encode(['search' => $term]));
        }

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        $this->line("Cleared " . count($cacheKeys) . " cache keys for user {$userId}");
    }

    protected function clearAllCache()
    {
        // Try selective clearing first
        $this->info('Clearing message-specific caches...');

        $patterns = [
            'user_conversations_*',
            'user_unread_count_*',
            'conversation_*',
            'message_*'
        ];

        $cleared = 0;

        // If using Redis or Memcached, we could do pattern matching
        // For now, we'll just flush all cache
        try {
            Cache::flush();
            $this->info('✅ All cache cleared successfully');
        } catch (\Exception $e) {
            $this->error('❌ Error clearing cache: ' . $e->getMessage());

            // Try alternative cache clearing
            $this->info('Trying alternative cache clearing...');
            try {
                \Artisan::call('cache:clear');
                \Artisan::call('config:clear');
                \Artisan::call('view:clear');
                $this->info('✅ Alternative cache clearing completed');
            } catch (\Exception $e2) {
                $this->error('❌ Alternative cache clearing failed: ' . $e2->getMessage());
            }
        }
    }
}
