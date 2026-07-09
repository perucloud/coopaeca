<?php

final class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (user()) {
            Response::redirect('/dashboard');
        }
        return $next($request);
    }
}
