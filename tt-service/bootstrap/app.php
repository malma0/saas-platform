<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // Доверяем заголовкам прокси (нужно при работе за туннелем/HTTPS-прокси:
        // ngrok, Cloudflare Tunnel — чтобы Laravel генерировал https-ссылки).
        $middleware->trustProxies(at: '*');

        // Неавторизованных гостей в веб-контексте отправляем на вход в панель.
        $middleware->redirectGuestsTo(static function ($request) {
            if (! $request->expectsJson()) {
                return route('moonshine.login');
            }
            return null;
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        /**
         * Единый формат ошибок Client API (Фаза 13):
         *   { "success": false, "data": null, "message": "...", "errors"?: {...} }
         */
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null; // веб-запросы — стандартный рендер
            }

            // Ошибки валидации → 422 + errors
            if ($e instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => $e->getMessage(),
                    'errors'  => $e->errors(),
                ], 422);
            }

            // Не аутентифицирован → 401
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => 'Требуется авторизация.',
                ], 401);
            }

            // Сущность не найдена → 404
            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => 'Ресурс не найден.',
                ], 404);
            }

            // Остальные HTTP-исключения — сохраняем их статус
            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => $e->getMessage() ?: 'Ошибка запроса.',
                ], $e->getStatusCode());
            }

            return null; // прочее — пусть Laravel решает (500 в проде скрыт)
        });
    })->create();
