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
                if ($e instanceof ModelNotFoundException) {
                    $model = class_basename($e->getModel());
                    return response()->json([
                        'success' => false,
                        'message' => "{$model} resource not found",
                        'errors' => $e->getMessage(),
                    ], 404);
                }

                if ($e instanceof NotFoundHttpException) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage(),
                        'errors' => $e->getMessage(),
                    ], 404);
                }


                // 401 - Unauthorized
                if ($e instanceof AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized',
                        'errors' => $e->getMessage(),
                    ], 401);
                }

                // 403 - Forbidden
                if ($e instanceof AuthorizationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Forbidden',
                        'errors' => $e->getMessage(),
                    ], 403);
                }

                // 422 - Validation Error
                if ($e instanceof ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $e->errors(), // Always include error data
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
                        'status' => $status,
                        'success' => false,
                        'message' => $message,
                        'errors' => $e->getMessage() ?: null,
                    ], $status);
                }

                // 500 - Internal Server Error (catch-all)
                return response()->json([
                    'statusCode' => 500,
                    'success' => false,
                    'message' => 'Internal server error',
                    'errors' => $e->getMessage(),
                    'trace' => config('app.debug') ? $e->getTrace() : null,
                ], 500);
            }
        });
    }
}
