<?php

namespace App\Services;

class ModuleRequestSigner
{
    public function sign(
        string $moduleId,
        string $requestId,
        string $timestamp,
        string $method,
        string $requestTarget,
        string $body,
        string $secret,
    ): string {
        $canonical = implode("\n", [
            $moduleId,
            $requestId,
            $timestamp,
            strtoupper($method),
            $requestTarget,
            hash('sha256', $body),
        ]);

        return hash_hmac('sha256', $canonical, $secret);
    }
}
