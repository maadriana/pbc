<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\PbcPermissionException;

class CheckPbcPermission
{
    /**
     * Handle an incoming request for Laravel 12
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip permission check for certain routes
        if ($this->shouldSkipPermissionCheck($request)) {
            return $next($request);
        }

        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Get the route information (Laravel 12 compatible)
        $route = $request->route();
        if (!$route) {
            return $next($request);
        }

        $routeName = $route->getName();
        $controller = $this->getControllerFromRoute($route);
        $method = $this->getMethodFromRoute($route);

        // Get required permissions
        $permissions = $this->getPermissionMapping($controller, $method, $routeName);

        // Check permissions
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                if (!$request->user()->hasPermission($permission)) {
                    // Throw custom exception for better error handling
                    throw new PbcPermissionException($permission);
                }
            }
        }

        return $next($request);
    }

    /**
     * Check if permission validation should be skipped
     */
    private function shouldSkipPermissionCheck(Request $request): bool
    {
        $skipRoutes = [
            'auth.login',
            'auth.logout',
            'auth.register',
            'dashboard.public',
            'health',
        ];

        $routeName = $request->route()?->getName();
        return in_array($routeName, $skipRoutes);
    }

    /**
     * Extract controller from route (Laravel 12 compatible)
     */
    private function getControllerFromRoute($route): string
    {
        $action = $route->getAction();

        if (isset($action['controller'])) {
            if (is_string($action['controller'])) {
                return class_basename(explode('@', $action['controller'])[0]);
            }

            if (is_array($action['controller'])) {
                return class_basename($action['controller'][0]);
            }
        }

        return '';
    }

    /**
     * Extract method from route (Laravel 12 compatible)
     */
    private function getMethodFromRoute($route): string
    {
        $action = $route->getAction();

        if (isset($action['controller'])) {
            if (is_string($action['controller']) && str_contains($action['controller'], '@')) {
                return explode('@', $action['controller'])[1];
            }

            if (is_array($action['controller']) && isset($action['controller'][1])) {
                return $action['controller'][1];
            }
        }

        // Check route methods
        $methods = $route->methods();
        if (in_array('GET', $methods)) return 'index';
        if (in_array('POST', $methods)) return 'store';
        if (in_array('PUT', $methods) || in_array('PATCH', $methods)) return 'update';
        if (in_array('DELETE', $methods)) return 'destroy';

        return '';
    }

    /**
     * Get permission mapping (Enhanced for Laravel 12)
     */
    private function getPermissionMapping(string $controller, string $method, ?string $routeName = null): array
    {
        // Route-based permissions (Laravel 12 style)
        $routePermissions = [
            'pbc.dashboard' => ['view_dashboard'],
            'pbc.users.index' => ['view_user'],
            'pbc.users.create' => ['create_user'],
            'pbc.users.store' => ['create_user'],
            'pbc.users.show' => ['view_user'],
            'pbc.users.edit' => ['edit_user'],
            'pbc.users.update' => ['edit_user'],
            'pbc.users.destroy' => ['delete_user'],
            'pbc.clients.index' => ['view_client'],
            'pbc.clients.store' => ['create_client'],
            'pbc.projects.index' => ['view_project'],
            'pbc.requests.index' => ['view_pbc_request'],
            'pbc.documents.upload' => ['upload_document'],
            'pbc.documents.approve' => ['approve_document'],
            'pbc.reports.export' => ['export_reports'],
        ];

        if ($routeName && isset($routePermissions[$routeName])) {
            return $routePermissions[$routeName];
        }

        // Fallback to controller-based permissions
        $controllerPermissions = [
            'UserController' => [
                'index' => ['view_user'],
                'store' => ['create_user'],
                'show' => ['view_user'],
                'update' => ['edit_user'],
                'destroy' => ['delete_user'],
                'permissions' => ['view_user'],
                'updatePermissions' => ['manage_permissions'],
            ],
            'ClientController' => [
                'index' => ['view_client'],
                'store' => ['create_client'],
                'show' => ['view_client'],
                'update' => ['edit_client'],
                'destroy' => ['delete_client'],
            ],
            'ProjectController' => [
                'index' => ['view_project'],
                'store' => ['create_project'],
                'show' => ['view_project'],
                'update' => ['edit_project'],
                'destroy' => ['delete_project'],
            ],
            'PbcRequestController' => [
                'index' => ['view_pbc_request'],
                'store' => ['create_pbc_request'],
                'show' => ['view_pbc_request'],
                'update' => ['edit_pbc_request'],
                'destroy' => ['delete_pbc_request'],
                'complete' => ['edit_pbc_request'],
                'bulkUpdate' => ['edit_pbc_request'],
            ],
            'PbcDocumentController' => [
                'index' => ['view_document'],
                'store' => ['upload_document'],
                'download' => ['download_document'],
                'approve' => ['approve_document'],
                'reject' => ['approve_document'],
                'destroy' => ['delete_document'],
            ],
            'DashboardController' => [
                'index' => ['view_dashboard'],
                'stats' => ['view_dashboard'],
                'recentActivity' => ['view_dashboard'],
            ],
        ];

        return $controllerPermissions[$controller][$method] ?? [];
    }
}
