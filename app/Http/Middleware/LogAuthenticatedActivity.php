<?php

namespace App\Http\Middleware;

use App\Services\Audit\ActivityLogService;
use App\Support\ContentDispositionFilename;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogAuthenticatedActivity
{
    /**
     * @var array<string, mixed>|null
     */
    private static ?array $pendingHttpAudit = null;

    public function handle(Request $request, Closure $next): Response
    {
        self::$pendingHttpAudit = null;

        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $user->loadMissing('roles');

        $snapshot = [
            'id' => $user->getAuthIdentifier(),
            'roles' => $user->roles->pluck('name')->values()->all(),
        ];

        if ($this->shouldSkipPath($request)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if ($this->shouldSkipRouteName($routeName)) {
            return $next($request);
        }

        self::$pendingHttpAudit = [
            'snapshot' => $snapshot,
            'route_name' => $routeName,
        ];

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $pending = self::$pendingHttpAudit;
        self::$pendingHttpAudit = null;

        if ($pending === null || ! isset($pending['snapshot']['id'])) {
            return;
        }

        $snapshot = $pending['snapshot'];
        $routeName = $pending['route_name'];

        if ($this->shouldSkipPath($request)) {
            return;
        }

        if ($this->shouldSkipRouteName($routeName)) {
            return;
        }

        $method = strtoupper($request->method());
        if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
            return;
        }

        $service = app(ActivityLogService::class);

        if ($this->isFileDownloadResponse($response)) {
            if ($routeName !== null && in_array($routeName, config('activity_log.http_audit_excluded_route_names', []), true)) {
                return;
            }

            $contentDisposition = $response->headers->get('Content-Disposition');
            $downloadFilename = $this->resolveDownloadFilename($response, $contentDisposition);
            $downloadLabel = $this->resolveDownloadLabel($routeName);
            $description = $downloadLabel !== ''
                ? sprintf('Файл татсан %s', $downloadLabel)
                : 'Файл татсан';

            $service->record(
                'file.download',
                $description,
                null,
                array_filter([
                    'route' => $routeName,
                    'path' => $request->path(),
                    'method' => $method,
                    'http_status' => $response->getStatusCode(),
                    'download_label' => $downloadLabel !== '' ? $downloadLabel : null,
                    'download_filename' => $downloadFilename,
                    'content_disposition' => $contentDisposition,
                    'roles' => $snapshot['roles'],
                ]),
                (int) $snapshot['id'],
            );

            return;
        }

        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        if ($routeName !== null && in_array($routeName, config('activity_log.http_audit_excluded_route_names', []), true)) {
            return;
        }

        $action = 'http.'.strtolower($method);
        $label = $routeName ?? $request->path();
        $description = sprintf('%s хүсэлт — %s', $method, $label);

        $service->record(
            $action,
            $description,
            null,
            array_filter([
                'route' => $routeName,
                'path' => $request->path(),
                'method' => $method,
                'http_status' => $response->getStatusCode(),
                'roles' => $snapshot['roles'],
            ]),
            (int) $snapshot['id'],
        );
    }

    private function shouldSkipPath(Request $request): bool
    {
        foreach (config('activity_log.ignore_path_patterns', []) as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    private function shouldSkipRouteName(?string $routeName): bool
    {
        if ($routeName === null || $routeName === '') {
            return false;
        }

        return in_array($routeName, config('activity_log.ignore_route_names', []), true);
    }

    private function isFileDownloadResponse(Response $response): bool
    {
        if ($response instanceof BinaryFileResponse) {
            return true;
        }

        if ($response instanceof StreamedResponse) {
            $cd = Str::lower((string) $response->headers->get('Content-Disposition', ''));
            if (Str::contains($cd, 'attachment')) {
                return true;
            }

            $type = Str::lower((string) $response->headers->get('Content-Type', ''));
            if (Str::contains($type, 'spreadsheetml') || Str::contains($type, 'ms-excel')) {
                return true;
            }
        }

        return false;
    }

    private function resolveDownloadFilename(Response $response, ?string $contentDisposition): ?string
    {
        $fromHeader = ContentDispositionFilename::fromHeader($contentDisposition);
        if ($fromHeader !== null && $fromHeader !== '') {
            return $fromHeader;
        }

        if ($response instanceof BinaryFileResponse) {
            $file = $response->getFile();
            if ($file !== null) {
                return $file->getFilename();
            }
        }

        return null;
    }

    private function resolveDownloadLabel(?string $routeName): string
    {
        if ($routeName === null || $routeName === '') {
            return '';
        }

        $labels = config('activity_log.download_route_labels', []);

        return $labels[$routeName] ?? '';
    }
}
