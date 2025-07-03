<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_email',
        'user_name',
        'action',
        'model_type',
        'model_id',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'category',
        'severity',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function model()
    {
        return $this->morphTo('model', 'model_type', 'model_id');
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByModelType($query, $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->where('severity', 'high');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    // Helper methods
    public function getDisplayActionAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->action));
    }

    public function getDisplayCategoryAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->category));
    }

    public function getDisplaySeverityAttribute()
    {
        return ucfirst($this->severity);
    }

    public function getSeverityBadgeClass()
    {
        return match($this->severity) {
            'low' => 'badge-success',
            'medium' => 'badge-warning',
            'high' => 'badge-danger',
            'critical' => 'badge-dark',
            default => 'badge-secondary'
        };
    }

    public function getCategoryBadgeClass()
    {
        return match($this->category) {
            'user' => 'badge-primary',
            'client' => 'badge-info',
            'project' => 'badge-success',
            'pbc_request' => 'badge-warning',
            'document' => 'badge-danger',
            'system' => 'badge-dark',
            default => 'badge-secondary'
        };
    }

    public function getModelDisplayName()
    {
        if (!$this->model) {
            return $this->model_type . " (ID: {$this->model_id})";
        }

        switch ($this->model_type) {
            case User::class:
                return "User: {$this->model->name}";
            case Client::class:
                return "Client: {$this->model->name}";
            case Project::class:
                return "Project: {$this->model->display_name}";
            case PbcRequest::class:
                return "PBC Request: {$this->model->title}";
            case PbcRequestItem::class:
                return "PBC Item: {$this->model->getDisplayName()}";
            case PbcSubmission::class:
                return "Document: {$this->model->original_filename}";
            default:
                return class_basename($this->model_type) . " (ID: {$this->model_id})";
        }
    }

    public function getUserDisplayName()
    {
        if ($this->user) {
            return $this->user->name;
        }

        return $this->user_name ?? $this->user_email ?? 'Unknown User';
    }

    // FIXED: Renamed from hasChanges() to hasValueChanges() to avoid conflict with Eloquent's hasChanges()
    public function hasValueChanges()
    {
        return !empty($this->old_values) || !empty($this->new_values);
    }

    public function getChangedFields()
    {
        if (!$this->hasValueChanges()) {
            return [];
        }

        $oldValues = $this->old_values ?? [];
        $newValues = $this->new_values ?? [];

        $changedFields = [];
        $allFields = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($allFields as $field) {
            $oldValue = $oldValues[$field] ?? null;
            $newValue = $newValues[$field] ?? null;

            if ($oldValue !== $newValue) {
                $changedFields[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changedFields;
    }

    public function getFormattedChanges()
    {
        $changes = $this->getChangedFields();
        $formatted = [];

        foreach ($changes as $field => $values) {
            $fieldName = ucwords(str_replace(['_', '-'], ' ', $field));
            $oldValue = $this->formatValue($values['old']);
            $newValue = $this->formatValue($values['new']);

            $formatted[] = "{$fieldName}: {$oldValue} → {$newValue}";
        }

        return $formatted;
    }

    private function formatValue($value)
    {
        if ($value === null) {
            return '(empty)';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_string($value) && strlen($value) > 50) {
            return substr($value, 0, 50) . '...';
        }

        return (string) $value;
    }

    public function getCreatedAtFormatted()
    {
        return $this->created_at->format('M j, Y \a\t g:i A');
    }

    public function getTimeAgo()
    {
        return $this->created_at->diffForHumans();
    }

    public function getBrowserInfo()
    {
        if (!$this->user_agent) {
            return 'Unknown';
        }

        // Simple browser detection (you might want to use a proper library)
        $browser = 'Unknown';
        if (strpos($this->user_agent, 'Chrome') !== false) {
            $browser = 'Chrome';
        } elseif (strpos($this->user_agent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($this->user_agent, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (strpos($this->user_agent, 'Edge') !== false) {
            $browser = 'Edge';
        } elseif (strpos($this->user_agent, 'Opera') !== false) {
            $browser = 'Opera';
        }

        return $browser;
    }

    public function getPlatformInfo()
    {
        if (!$this->user_agent) {
            return 'Unknown';
        }

        $platform = 'Unknown';
        if (strpos($this->user_agent, 'Windows') !== false) {
            $platform = 'Windows';
        } elseif (strpos($this->user_agent, 'Mac') !== false) {
            $platform = 'Mac';
        } elseif (strpos($this->user_agent, 'Linux') !== false) {
            $platform = 'Linux';
        } elseif (strpos($this->user_agent, 'Android') !== false) {
            $platform = 'Android';
        } elseif (strpos($this->user_agent, 'iOS') !== false) {
            $platform = 'iOS';
        }

        return $platform;
    }

    public function getLocationInfo()
    {
        // This would integrate with a GeoIP service
        // For now, just return the IP address
        return $this->ip_address ?? 'Unknown';
    }

    public function isHighRiskAction()
    {
        $highRiskActions = [
            'deleted',
            'force_deleted',
            'rejected',
            'cancelled',
            'login_failed',
            'permission_changed',
            'password_reset',
            'account_locked',
        ];

        return in_array($this->action, $highRiskActions) || $this->severity === 'critical';
    }

    public function isSensitiveData()
    {
        $sensitiveModels = [
            User::class,
            Client::class,
            PbcSubmission::class,
        ];

        return in_array($this->model_type, $sensitiveModels);
    }

    public function getRelatedLogs($limit = 10)
    {
        return static::where('model_type', $this->model_type)
                    ->where('model_id', $this->model_id)
                    ->where('id', '!=', $this->id)
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public function getUserActivityContext($hours = 24)
    {
        return static::where('user_id', $this->user_id)
                    ->where('created_at', '>=', $this->created_at->subHours($hours))
                    ->where('created_at', '<=', $this->created_at->addHours($hours))
                    ->where('id', '!=', $this->id)
                    ->orderBy('created_at')
                    ->get();
    }

    // Static methods for logging activities
    public static function logActivity($action, $model, $description, $user = null, $severity = 'medium', $category = 'system')
    {
        $user = $user ?? auth()->user();

        $data = [
            'action' => $action,
            'description' => $description,
            'category' => $category,
            'severity' => $severity,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ];

        if ($user) {
            $data['user_id'] = $user->id;
            $data['user_email'] = $user->email;
            $data['user_name'] = $user->name;
        }

        if ($model) {
            $data['model_type'] = get_class($model);
            $data['model_id'] = $model->id;
        }

        return static::create($data);
    }

    public static function logModelChange($action, $model, $oldValues = [], $newValues = [], $user = null)
    {
        $user = $user ?? auth()->user();

        $description = "Model " . class_basename($model) . " was {$action}";
        if ($model && method_exists($model, 'getDisplayName')) {
            $description = $model->getDisplayName() . " was {$action}";
        } elseif ($model && isset($model->name)) {
            $description = $model->name . " was {$action}";
        }

        $data = [
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'category' => static::determineCategoryFromModel($model),
            'severity' => static::determineSeverityFromAction($action),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ];

        if ($user) {
            $data['user_id'] = $user->id;
            $data['user_email'] = $user->email;
            $data['user_name'] = $user->name;
        }

        return static::create($data);
    }

    public static function logLogin($user, $success = true)
    {
        $action = $success ? 'login' : 'login_failed';
        $description = $success
            ? "User {$user->name} logged in successfully"
            : "Failed login attempt for {$user->email}";

        return static::logActivity($action, $user, $description, $user,
            $success ? 'low' : 'high', 'user');
    }

    public static function logLogout($user)
    {
        return static::logActivity('logout', $user, "User {$user->name} logged out",
            $user, 'low', 'user');
    }

    public static function logPbcActivity($action, $pbcModel, $description, $user = null, $severity = 'medium')
    {
        return static::logActivity($action, $pbcModel, $description, $user,
            $severity, 'pbc_request');
    }

    public static function logDocumentActivity($action, $document, $description, $user = null)
    {
        return static::logActivity($action, $document, $description, $user,
            'medium', 'document');
    }

    public static function logSystemActivity($action, $description, $user = null, $severity = 'low')
    {
        return static::logActivity($action, null, $description, $user,
            $severity, 'system');
    }

    private static function determineCategoryFromModel($model)
    {
        if ($model instanceof User) return 'user';
        if ($model instanceof Client) return 'client';
        if ($model instanceof Project) return 'project';
        if ($model instanceof PbcRequest || $model instanceof PbcRequestItem) return 'pbc_request';
        if ($model instanceof PbcSubmission) return 'document';

        return 'system';
    }

    private static function determineSeverityFromAction($action)
    {
        $highSeverityActions = ['deleted', 'force_deleted', 'rejected'];
        $mediumSeverityActions = ['created', 'updated', 'approved', 'submitted'];

        if (in_array($action, $highSeverityActions)) return 'high';
        if (in_array($action, $mediumSeverityActions)) return 'medium';

        return 'low';
    }

    // Cleanup methods
    public static function cleanupOldLogs($days = 2555) // 7 years default
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }

    public static function archiveOldLogs($days = 365)
    {
        // This would move old logs to an archive table or external storage
        // Implementation depends on your archival strategy
        return static::where('created_at', '<', now()->subDays($days))->count();
    }

    public static function getSystemStats($days = 30)
    {
        $query = static::where('created_at', '>=', now()->subDays($days));

        return [
            'total_activities' => $query->count(),
            'unique_users' => $query->distinct('user_id')->count('user_id'),
            'by_category' => $query->groupBy('category')->selectRaw('category, count(*) as count')->pluck('count', 'category'),
            'by_severity' => $query->groupBy('severity')->selectRaw('severity, count(*) as count')->pluck('count', 'severity'),
            'high_risk_activities' => $query->where('severity', 'high')->orWhere('severity', 'critical')->count(),
            'recent_critical' => static::where('severity', 'critical')->recent(7)->count(),
        ];
    }
}
