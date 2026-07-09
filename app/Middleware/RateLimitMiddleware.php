<?php

final class RateLimitMiddleware implements MiddlewareInterface
{
    private string $key;
    private int $maxAttempts;
    private int $seconds;

    public function __construct(string $params = 'login,5,60')
    {
        [$this->key, $max, $secs] = explode(',', $params, 3) + ['login', '5', '60'];
        $this->maxAttempts = (int)$max;
        $this->seconds     = (int)$secs;
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->isPost() && !rate_limit($this->key . '_' . $request->ip(), $this->maxAttempts, $this->seconds)) {
            Response::abort(429, 'Demasiados intentos. Intenta mas tarde.');
        }
        return $next($request);
    }
}
