<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chặn cả nhóm route khi công tắc tính năng trong `config/aptis.php` đang TẮT
 * (trả 404 như thể trang không tồn tại). Dùng: `->middleware('feature:classes_enabled')`.
 */
class FeatureEnabled
{
    public function handle(Request $request, Closure $next, string $key): Response
    {
        abort_unless(config("aptis.$key"), 404);

        return $next($request);
    }
}
