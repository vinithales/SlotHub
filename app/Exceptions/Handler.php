<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
    public function render($request, Throwable $exception)
    {
        // Se for uma requisição API
        if ($request->is('api/*')) {
            // Método não permitido (405)
            if ($exception instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'error' => 'Método não permitido',
                    'allowed_methods' => explode(',', $exception->getHeaders()['Allow'])
                ], 405);
            }

            // Rota não encontrada (404)
            if ($exception instanceof NotFoundHttpException) {
                return response()->json([
                    'error' => 'Endpoint não encontrado'
                ], 404);
            }
        }

        return parent::render($request, $exception);
    }
}


//USAR HELPER PARA TRATAR ERROS
