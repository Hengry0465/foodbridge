<?php

namespace App\Http\Middleware;

use App\Services\ModuleRequestSigner;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VerifyModuleRequest
{
    public function __construct(private ModuleRequestSigner $signer) {}

    public function handle(Request $request, Closure $next): Response
    {
        $moduleId = (string) $request->header('X-Module-ID');
        $requestId = (string) $request->header('X-Request-ID');
        $timestamp = (string) $request->header('X-Timestamp');
        $providedSignature = (string) $request->header('X-Signature');
        $secret = (string) config('integrations.module_api_key');

        if ($secret === '') {
            return $this->error($requestId, 'Module API authentication is not configured.', 503);
        }

        if ($moduleId === '' || ! preg_match('/^[A-Za-z0-9._-]{2,40}$/', $moduleId)) {
            return $this->error($requestId, 'A valid X-Module-ID header is required.', 401);
        }

        if ($requestId === '' || ! preg_match('/^[A-Za-z0-9._:-]{8,100}$/', $requestId)) {
            return $this->error($requestId, 'A valid X-Request-ID header is required.', 400);
        }

        try {
            $sentAt = CarbonImmutable::parse($timestamp);
        } catch (Throwable) {
            return $this->error($requestId, 'A valid ISO-8601 X-Timestamp header is required.', 400);
        }

        $clockSkew = (int) config('integrations.allowed_clock_skew_seconds', 300);
        if (abs(now()->getTimestamp() - $sentAt->getTimestamp()) > $clockSkew) {
            return $this->error($requestId, 'The request timestamp is outside the allowed clock-skew window.', 408);
        }

        $expectedSignature = $this->signer->sign(
            $moduleId,
            $requestId,
            $timestamp,
            $request->method(),
            $request->getRequestUri(),
            $request->getContent(),
            $secret,
        );

        if ($providedSignature === '' || ! hash_equals($expectedSignature, $providedSignature)) {
            return $this->error($requestId, 'The module request signature is invalid.', 401);
        }

        $request->attributes->set('requestID', $requestId);

        return $next($request);
    }

    private function error(string $requestId, string $message, int $code): JsonResponse
    {
        return response()->json([
            'requestID' => $requestId ?: null,
            'timestamp' => now()->toIso8601String(),
            'status' => 'error',
            'message' => $message,
        ], $code);
    }
}
