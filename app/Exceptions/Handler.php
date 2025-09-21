<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

        // Handle ModelNotFoundException globally
        $this->renderable(function (ModelNotFoundException $e, $request) {
            // Optional: detect if it's an API request
            if ($request->is('api/*')) {
                // Extract the model name from the exception
                $model = class_basename($e->getModel());

                return response()->json([
                    'success' => false,
                    'message' => "{$model} not found"
                ], 404);
            }
        });
    }
}
