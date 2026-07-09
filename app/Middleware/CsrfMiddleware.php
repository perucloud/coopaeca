<?php

final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->isPost()) {
            verify_csrf();
        }
        return $next($request);
    }
}
