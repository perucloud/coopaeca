<?php

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!user()) {
            Response::redirect('/login');
        }
        return $next($request);
    }
}
