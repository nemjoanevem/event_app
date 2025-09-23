<?php

namespace App\Exceptions;

namespace App\Exceptions;

use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends \Illuminate\Foundation\Exceptions\Handler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            if (!app()->runningInConsole()) {
                $req = request();
                Log::error($e->getMessage(), [
                    'exception'  => get_class($e),
                    'request_id' => $req->attributes->get('request_id'),
                    'user_id'    => optional($req->user())->id,
                    'url'        => $req->fullUrl(),
                    'method'     => $req->method(),
                ]);
            }
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->toApiResponse($e, $request);
            }
        });
    }

    protected function toApiResponse(Throwable $e, $request): JsonResponse
    {
        $status = 500;
        $message = trans('errors.500');
        $errors = null;

        if ($e instanceof ValidationException) {
            $status = 422;
            $message = trans('errors.422');
            $errors = $e->errors();
        } elseif ($e instanceof AuthenticationException) {
            $status = 401;
            $message = trans('errors.401');
        } elseif ($e instanceof AuthorizationException) {
            $status = 403;
            $message = trans('errors.403');
        } elseif ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            $status = 404;
            $message = trans('errors.404');
        } elseif ($e instanceof MethodNotAllowedHttpException) {
            $status = 405;
            $message = trans('errors.405');
        } elseif ($e instanceof ThrottleRequestsException) {
            $status = 429;
            $message = trans('errors.429');
        } elseif ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $message = $e->getMessage() ?: $message;
        } elseif ($e instanceof QueryException) {
            // Database errors are 500, but we don't want to leak details
            $status = 500;
            $message = trans('errors.500');
        }

        return response()->json([
            'message'   => $message,
            'errors'    => $errors,
            'requestId' => $request->attributes->get('request_id'),
            'timestamp' => now()->toIso8601String(),
        ], $status);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message'   => trans('errors.401'),
                'requestId' => $request->attributes->get('request_id'),
                'timestamp' => now()->toIso8601String(),
            ], 401);
        }

        return redirect()->guest(route('login'));
    }
}
