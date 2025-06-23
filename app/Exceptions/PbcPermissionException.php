<?php

namespace App\Exceptions;

use Exception;

class PbcPermissionException extends Exception
{
    private string $requiredPermission;

    public function __construct(string $requiredPermission, string $message = null)
    {
        $this->requiredPermission = $requiredPermission;
        $message = $message ?? "Permission '{$requiredPermission}' is required to perform this action.";

        parent::__construct($message, 403);
    }

    public function getRequiredPermission(): string
    {
        return $this->requiredPermission;
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'required_permission' => $this->requiredPermission,
            'error_code' => 'INSUFFICIENT_PERMISSIONS'
        ], 403);
    }
}
