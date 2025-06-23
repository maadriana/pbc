<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\PbcComment;
use App\Models\PbcReminder;

class PbcServiceProvider extends ServiceProvider
{
    /**
     * Register services for Laravel 12
     */
    public function register(): void
    {
        // Register PBC services as singletons
        $this->app->singleton(\App\Services\AuthService::class);
        $this->app->singleton(\App\Services\DashboardService::class);
        $this->app->singleton(\App\Services\UserService::class);
        $this->app->singleton(\App\Services\ClientService::class);
        $this->app->singleton(\App\Services\ProjectService::class);
        $this->app->singleton(\App\Services\PbcRequestService::class);
        $this->app->singleton(\App\Services\PbcDocumentService::class);
        $this->app->singleton(\App\Services\PbcCommentService::class);
        $this->app->singleton(\App\Services\PbcReminderService::class);
        $this->app->singleton(\App\Services\PbcCategoryService::class);
        $this->app->singleton(\App\Services\ReportService::class);

        // Merge PBC configuration
        $this->mergeConfigFrom(__DIR__.'/../../config/pbc.php', 'pbc');
    }

    /**
     * Bootstrap services for Laravel 12
     */
    public function boot(): void
    {
        // Register authorization gates
        $this->registerGates();

        // Register view composers for shared data
        $this->registerViewComposers();

        // Register custom validation rules
        $this->registerValidationRules();

        // Boot additional PBC features
        $this->bootPbcFeatures();
    }

    /**
     * Register authorization gates
     */
    private function registerGates(): void
    {
        // Model-specific gates
        Gate::define('edit_comment', function (User $user, PbcComment $comment) {
            return $user->id === $comment->user_id || $user->isSystemAdmin();
        });

        Gate::define('delete_comment', function (User $user, PbcComment $comment) {
            return $user->id === $comment->user_id || $user->isSystemAdmin();
        });

        Gate::define('read_reminder', function (User $user, PbcReminder $reminder) {
            return $user->id === $reminder->sent_to || $user->id === $reminder->sent_by;
        });

        // Permission-based gates
        $permissions = [
            'view_user', 'create_user', 'edit_user', 'delete_user',
            'view_client', 'create_client', 'edit_client', 'delete_client',
            'view_project', 'create_project', 'edit_project', 'delete_project',
            'view_pbc_request', 'create_pbc_request', 'edit_pbc_request', 'delete_pbc_request',
            'view_document', 'upload_document', 'download_document', 'approve_document', 'delete_document',
            'view_reminder', 'send_reminder',
            'view_category', 'manage_categories',
            'export_reports', 'view_audit_log', 'view_dashboard',
            'manage_permissions', 'manage_settings',
        ];

        foreach ($permissions as $permission) {
            Gate::define($permission, function (User $user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }
    }

    /**
     * Register view composers
     */
    private function registerViewComposers(): void
    {
        if (function_exists('view')) {
            view()->composer('*', function ($view) {
                if (auth()->check()) {
                    $view->with('currentUser', auth()->user());
                    $view->with('userPermissions', auth()->user()->permissions->pluck('permission')->toArray());
                    $view->with('pbcConfig', config('pbc'));
                }
            });
        }
    }

    /**
     * Register custom validation rules
     */
    private function registerValidationRules(): void
    {
        \Illuminate\Support\Facades\Validator::extend('pbc_file_type', function ($attribute, $value, $parameters, $validator) {
            if (!$value instanceof \Illuminate\Http\UploadedFile) {
                return false;
            }

            $allowedTypes = config('pbc.file_upload.allowed_types', []);
            $extension = strtolower($value->getClientOriginalExtension());

            return in_array($extension, $allowedTypes);
        });
    }

    /**
     * Boot PBC-specific features
     */
    private function bootPbcFeatures(): void
    {
        // Auto-register PBC commands only if they exist
        if ($this->app->runningInConsole()) {
            $commands = [];

            // Check if command classes exist before registering
            if (class_exists(\App\Console\Commands\PbcOverdueCheckCommand::class)) {
                $commands[] = \App\Console\Commands\PbcOverdueCheckCommand::class;
            }

            if (class_exists(\App\Console\Commands\PbcSendRemindersCommand::class)) {
                $commands[] = \App\Console\Commands\PbcSendRemindersCommand::class;
            }

            if (class_exists(\App\Console\Commands\PbcCleanupLogsCommand::class)) {
                $commands[] = \App\Console\Commands\PbcCleanupLogsCommand::class;
            }

            if (!empty($commands)) {
                $this->commands($commands);
            }
        }
    }
}
