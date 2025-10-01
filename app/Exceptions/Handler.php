<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {

                // 404 - Resource not found
                if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Resource not found',
                        'data' => null,
                    ], 404);
                }

                // 401 - Unauthorized
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized',
                        'data' => null,
                    ], 401);
                }

                // 403 - Forbidden
                if ($e instanceof AuthorizationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Forbidden',
                        'data' => null,
                    ], 403);
                }

                // 422 - Validation Error
                if ($e instanceof ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'data' => $e->errors(), // Always include error data
                    ], 422);
                }

                // Handle other HTTP exceptions
                if ($e instanceof HttpException) {
                    $status = $e->getStatusCode();
                    $message = $e->getMessage() ?: match ($status) {
                        400 => 'Bad request',
                        409 => 'Conflict',
                        default => 'HTTP error',
                    };

                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'data' => [
                            'status' => $status,
                            'error' => $e->getMessage() ?: null,
                        ],
                    ], $status);
                }

                // 500 - Internal Server Error (catch-all)
                return response()->json([
                    'success' => false,
                    'message' => 'Internal server error',
                    'data' => [
                        'error' => $e->getMessage(),
                        'trace' => config('app.debug') ? $e->getTrace() : null,
                    ],
                ], 500);
            }
        });
    }
}
