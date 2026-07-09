<?php

final class PermissionMiddleware implements MiddlewareInterface
{
    public function __construct(private string $permission)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        [$module, $action] = explode('.', $this->permission, 2) + ['', 'view'];
        require_permission($module, $action);
        return $next($request);
    }
}
