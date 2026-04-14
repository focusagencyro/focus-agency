<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class FocusPageController extends Controller
{
    private const CONTENT_FILE = 'focus/pages.json';

    public function home(): View
    {
        return $this->renderPath('/');
    }

    public function fallback(Request $request): View
    {
        $path = '/'.ltrim($request->path(), '/');

        if ($path === '/index.php') {
            $path = '/';
        }

        return $this->renderPath($path);
    }

    public function sitemap(): View
    {
        $data = $this->loadContentData();

        return view('focus.sitemap', [
            'pages' => $data['pages'],
            'generatedAt' => $data['generated_at'],
            'pageCount' => $data['page_count'],
        ]);
    }

    private function renderPath(string $path): View
    {
        try {
            $normalizedPath = $this->normalizePath($path);
            $data = $this->loadContentData();

            $page = $data['pages']->firstWhere('path', $normalizedPath);

            if ($page === null) {
                abort(404);
            }

            $related = $data['pages']
                ->filter(fn (array $entry) => $entry['path'] !== $normalizedPath)
                ->take(6)
                ->values();

            $primaryNavigation = $data['pages']
                ->filter(function (array $entry): bool {
                    $path = $entry['path'];
                    if ($path === '/') {
                        return false;
                    }

                    if (substr_count(trim($path, '/'), '/') > 0) {
                        return false;
                    }

                    foreach (['category', 'tag', 'product', 'portfolio', 'team-member', 'booking-confirmation'] as $segment) {
                        if (str_contains($path, '/'.$segment)) {
                            return false;
                        }
                    }

                    return true;
                })
                ->take(8)
                ->values();

            return view('focus.page', [
                'page' => $page,
                'relatedPages' => $related,
                'primaryNavigation' => $primaryNavigation,
                'generatedAt' => $data['generated_at'],
                'pageCount' => $data['page_count'],
            ]);
        } catch (\Throwable $e) {
            logger()->error('FocusPageController renderPath failed.', [
                'path' => $path,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw $e;
        }
    }

    private function loadContentData(): array
    {
        $contentPath = storage_path('app/'.self::CONTENT_FILE);
        $json = @file_get_contents($contentPath);

        if (!is_string($json) || trim($json) === '') {
            logger()->error('Focus content file missing or empty.', [
                'path' => $contentPath,
                'exists' => file_exists($contentPath),
                'readable' => is_readable($contentPath),
            ]);
            abort(500, 'Content import is missing. Run: php scripts/import_focus_content.php');
        }

        $payload = json_decode($json, true);

        if (!is_array($payload) || !isset($payload['pages']) || !is_array($payload['pages'])) {
            logger()->error('Focus content file invalid.', [
                'path' => $contentPath,
                'json_error' => json_last_error_msg(),
            ]);
            abort(500, 'Imported content format is invalid.');
        }

        return [
            'generated_at' => (string) ($payload['generated_at'] ?? ''),
            'page_count' => (int) ($payload['page_count'] ?? count($payload['pages'])),
            'pages' => collect($payload['pages'])->map(function (array $page): array {
                return [
                    'path' => (string) ($page['path'] ?? '/'),
                    'source_url' => (string) ($page['source_url'] ?? ''),
                    'title' => (string) ($page['title'] ?? 'Untitled'),
                    'excerpt' => (string) ($page['excerpt'] ?? ''),
                    'sections' => isset($page['sections']) && is_array($page['sections']) ? $page['sections'] : [],
                    'image_placeholders' => isset($page['image_placeholders']) && is_array($page['image_placeholders']) ? $page['image_placeholders'] : [],
                ];
            }),
        ];
    }

    private function normalizePath(string $path): string
    {
        $normalizedPath = '/'.ltrim($path, '/');

        if ($normalizedPath !== '/') {
            $normalizedPath = rtrim($normalizedPath, '/');
        }

        return $normalizedPath;
    }
}
