<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Return a success JSON response
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    protected function success($data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ], $status);
    }

    /**
     * Return an error JSON response
     *
     * @param string $message
     * @param mixed $errors
     * @param int $status
     * @return JsonResponse
     */
    protected function error(string $message = 'Error', $errors = null, int $status = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString()
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a paginated JSON response
     *
     * @param LengthAwarePaginator $data
     * @param string $message
     * @return JsonResponse
     */
    protected function paginated($data, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'has_more_pages' => $data->hasMorePages(),
                'prev_page_url' => $data->previousPageUrl(),
                'next_page_url' => $data->nextPageUrl(),
                'first_page_url' => $data->url(1),
                'last_page_url' => $data->url($data->lastPage()),
                'path' => $data->path(),
            ],
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Return a validation error JSON response
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, $errors, 422);
    }

    /**
     * Return a not found JSON response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, null, 404);
    }

    /**
     * Return an unauthorized JSON response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorized(string $message = 'Unauthorized access'): JsonResponse
    {
        return $this->error($message, null, 401);
    }

    /**
     * Return a forbidden JSON response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbidden(string $message = 'Access forbidden'): JsonResponse
    {
        return $this->error($message, null, 403);
    }

    /**
     * Return a server error JSON response
     *
     * @param string $message
     * @param mixed $errors
     * @return JsonResponse
     */
    protected function serverError(string $message = 'Internal server error', $errors = null): JsonResponse
    {
        return $this->error($message, $errors, 500);
    }

    /**
     * Return a created resource JSON response
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    protected function created($data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Return a deleted resource JSON response
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function deleted(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return $this->success(null, $message, 200);
    }

    /**
     * Return a no content JSON response
     *
     * @return JsonResponse
     */
    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Handle API exceptions and return appropriate response
     *
     * @param \Exception $exception
     * @param string $defaultMessage
     * @return JsonResponse
     */
    protected function handleException(\Exception $exception, string $defaultMessage = 'An error occurred'): JsonResponse
    {
        // Log the exception
        \Log::error('API Exception: ' . $exception->getMessage(), [
            'exception' => $exception,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Return different responses based on exception type
        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return $this->validationError($exception->errors(), 'Validation failed');
        }

        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFound('Resource not found');
        }

        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return $this->forbidden('Access denied');
        }

        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return $this->notFound('Endpoint not found');
        }

        // For debugging in development
        if (config('app.debug')) {
            return $this->serverError($exception->getMessage(), [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace()
            ]);
        }

        // Generic error response for production
        return $this->serverError($defaultMessage);
    }

    /**
     * Extract pagination parameters from request
     *
     * @param Request $request
     * @param int $defaultPerPage
     * @param int $maxPerPage
     * @return array
     */
    protected function getPaginationParams(Request $request, int $defaultPerPage = 15, int $maxPerPage = 100): array
    {
        $perPage = (int) $request->get('per_page', $defaultPerPage);
        $page = (int) $request->get('page', 1);

        // Ensure reasonable limits
        $perPage = min(max($perPage, 1), $maxPerPage);
        $page = max($page, 1);

        return [
            'per_page' => $perPage,
            'page' => $page
        ];
    }

    /**
     * Extract sorting parameters from request
     *
     * @param Request $request
     * @param string $defaultSort
     * @param array $allowedSorts
     * @return array
     */
    protected function getSortParams(Request $request, string $defaultSort = 'created_at', array $allowedSorts = []): array
    {
        $sortBy = $request->get('sort_by', $defaultSort);
        $sortOrder = $request->get('sort_order', 'desc');

        // Validate sort field
        if (!empty($allowedSorts) && !in_array($sortBy, $allowedSorts)) {
            $sortBy = $defaultSort;
        }

        // Validate sort order
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'desc';

        return [
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder
        ];
    }

    /**
     * Check if user has required permission
     *
     * @param string $permission
     * @param string $resource
     * @return bool
     */
    protected function checkPermission(string $permission, string $resource = null): bool
    {
        if (!auth()->check()) {
            return false;
        }

        return auth()->user()->hasPermission($permission, $resource);
    }

    /**
     * Ensure user has required permission or abort
     *
     * @param string $permission
     * @param string $resource
     * @param string $message
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function requirePermission(string $permission, string $resource = null, string $message = null): void
    {
        if (!$this->checkPermission($permission, $resource)) {
            $message = $message ?: "You do not have permission to {$permission}" . ($resource ? " {$resource}" : '');
            abort(403, $message);
        }
    }

    /**
     * Get current authenticated user
     *
     * @return \App\Models\User|null
     */
    protected function getCurrentUser()
    {
        return auth()->user();
    }

    /**
     * Log user activity
     *
     * @param string $action
     * @param string $description
     * @param array $data
     * @return void
     */
    protected function logActivity(string $action, string $description, array $data = []): void
    {
        \Log::info("User Activity: {$action}", [
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'description' => $description,
            'data' => $data,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
    }
}
