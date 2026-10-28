<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RecordActivity
{
    public function handle(Request $request, Closure $next)
    {
        $actor = $request->user()?->id;
        try {
            $response = $next($request);
        } catch (\Throwable $error) {
            $status = $error instanceof ValidationException ? 422 : ($error instanceof HttpExceptionInterface ? $error->getStatusCode() : 500);
            $this->record($request, $actor, $status);
            throw $error;
        }
        $this->record($request, $actor ?? $request->user()?->id, $response->getStatusCode());

        return $response;
    }

    private function record(Request $request, ?int $actor, int $status): void
    {
        // No payload, query, credentials, or session identifiers are logged.
        try {
            ActivityLog::create(['user_id' => $actor, 'action' => 'request', 'method' => $request->method(),
                'route' => $request->route()?->getName(), 'response_status' => $status]);
        } catch (\Throwable $error) {
            Log::critical('Log akses gagal disimpan.', ['route' => $request->route()?->getName(), 'status' => $status]);
        }
    }
}
