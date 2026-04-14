<?php

declare(strict_types=1);

const ROOT_SITEMAP = 'https://www.focusagency.ro/sitemap.xml';
const OUTPUT_FILE = __DIR__.'/../storage/app/focus/pages.json';

function fetchUrl(string $url): ?string
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 35,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'FocusAgency-Structured-Importer/1.0 (+https://www.focusagency.ro)',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_ENCODING => '',
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (!is_string($response) || $status < 200 || $status >= 400) {
        return null;
    }

    return $response;
}

function extractSitemapUrls(string $xml): array
{
    $document = @simplexml_load_string($xml);
    if ($document === false) {
        return [];
    }

    $namespaces = $document->getNamespaces(true);
    $sitemapNs = $namespaces[''] ?? 'http://www.sitemaps.org/schemas/sitemap/0.9';
    $document->registerXPathNamespace('s', $sitemapNs);

    $results = $document->xpath('//s:loc');
    if ($results === false) {
        return [];
    }

    $urls = [];
    foreach ($results as $loc) {
        $url = trim((string) $loc);
        if ($url !== '') {
            $urls[] = $url;
        }
    }

    return array_values(array_unique($urls));
}

function normalizePath(string $url): string
{
    $path = parse_url($url, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return '/';
    }

    $path = '/'.ltrim($path, '/');
    if ($path !== '/') {
        $path = rtrim($path, '/');
    }

    return $path;
}

function normalizeWhitespace(string $text): string
{
    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function pickContentNode(DOMDocument $dom): DOMNode
{
    $xpath = new DOMXPath($dom);
    $queries = [
        "//*[@id='content']",
        "//*[contains(@class, 'site-content')]",
        "//*[contains(@class, 'entry-content')]",
        "//*[contains(@class, 'elementor-location-single')]",
        "//*[contains(@class, 'elementor-widget-theme-post-content')]",
        '//main',
        '//article',
        '//body',
    ];

    foreach ($queries as $query) {
        $nodes = $xpath->query($query);
        if ($nodes === false || $nodes->length === 0) {
            continue;
        }

        foreach ($nodes as $node) {
            $text = normalizeWhitespace($node->textContent ?? '');
            if (mb_strlen($text) > 220) {
                return $node;
            }
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    return $body ?? $dom->documentElement;
}

function sanitizeNode(DOMNode $root, DOMDocument $dom): void
{
    $xpath = new DOMXPath($dom);

    foreach ([
        'script',
        'style',
        'noscript',
        'iframe',
        'svg',
        'canvas',
        'video',
        'audio',
        'form',
        'button',
        'input',
        'select',
        'textarea',
        'nav',
        'footer',
        'header',
    ] as $tag) {
        $nodes = $xpath->query('.//'.$tag, $root);
        if ($nodes === false) {
            continue;
        }

        for ($i = $nodes->length - 1; $i >= 0; $i--) {
            $node = $nodes->item($i);
            if ($node && $node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }
    }
}

function extractTitle(DOMDocument $dom, DOMNode $contentNode): string
{
    $xpath = new DOMXPath($dom);
    $h1 = $xpath->query('.//h1', $contentNode);

    if ($h1 !== false && $h1->length > 0) {
        $candidate = normalizeWhitespace($h1->item(0)?->textContent ?? '');
        if ($candidate !== '') {
            return $candidate;
        }
    }

    $titleTag = $dom->getElementsByTagName('title')->item(0);
    if ($titleTag) {
        $title = normalizeWhitespace($titleTag->textContent ?? '');
        if ($title !== '') {
            return $title;
        }
    }

    return 'Untitled';
}

function isNoiseText(string $text): bool
{
    $noisePrefixes = [
        'Lastudioicon',
        'Cookie',
        'Accept',
    ];

    foreach ($noisePrefixes as $prefix) {
        if (str_starts_with($text, $prefix)) {
            return true;
        }
    }

    return false;
}

function extractImagePlaceholders(DOMNode $root, DOMDocument $dom): array
{
    $xpath = new DOMXPath($dom);
    $images = $xpath->query('.//img', $root);
    if ($images === false || $images->length === 0) {
        return [];
    }

    $items = [];
    foreach ($images as $img) {
        $alt = normalizeWhitespace($img->attributes?->getNamedItem('alt')?->nodeValue ?? '');
        $src = normalizeWhitespace($img->attributes?->getNamedItem('src')?->nodeValue ?? '');
        $name = $alt !== '' ? $alt : basename((string) parse_url($src, PHP_URL_PATH));

        if ($name === '' || $name === '/' || $name === '.' || $name === '..') {
            $name = 'Focus Agency Visual';
        }

        $items[] = 'AI Image Placeholder: '.$name;
        if (count($items) >= 8) {
            break;
        }
    }

    return array_values(array_unique($items));
}

function extractSections(DOMNode $root, DOMDocument $dom, string $fallbackTitle): array
{
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('.//h1|.//h2|.//h3|.//h4|.//p|.//li|.//blockquote', $root);

    if ($nodes === false || $nodes->length === 0) {
        return [
            [
                'heading' => $fallbackTitle,
                'body' => [],
            ],
        ];
    }

    $sections = [];
    $currentHeading = $fallbackTitle;
    $currentBody = [];
    $seen = [];
    $addedItems = 0;

    foreach ($nodes as $node) {
        $tag = strtolower($node->nodeName);
        $text = normalizeWhitespace($node->textContent ?? '');

        if ($text === '' || mb_strlen($text) < 3 || mb_strlen($text) > 600 || isNoiseText($text)) {
            continue;
        }

        $key = mb_strtolower($text);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;

        if (in_array($tag, ['h1', 'h2', 'h3', 'h4'], true) && mb_strlen($text) <= 140) {
            if ($currentBody !== []) {
                $sections[] = [
                    'heading' => $currentHeading,
                    'body' => $currentBody,
                ];
            }

            $currentHeading = $text;
            $currentBody = [];
            continue;
        }

        $line = $tag === 'li' ? '• '.$text : $text;
        $currentBody[] = $line;
        $addedItems++;

        if ($addedItems >= 240) {
            break;
        }
    }

    if ($currentBody !== [] || $sections === []) {
        $sections[] = [
            'heading' => $currentHeading,
            'body' => $currentBody,
        ];
    }

    $sections = array_values(array_filter($sections, function (array $section): bool {
        return $section['heading'] !== '' || $section['body'] !== [];
    }));

    return $sections === [] ? [['heading' => $fallbackTitle, 'body' => []]] : $sections;
}

function excerptFromSections(array $sections): string
{
    $chunks = [];
    foreach ($sections as $section) {
        if (!empty($section['body'])) {
            foreach ($section['body'] as $line) {
                $chunks[] = $line;
                if (count($chunks) >= 3) {
                    break 2;
                }
            }
        }
    }

    $text = normalizeWhitespace(implode(' ', $chunks));
    if ($text === '') {
        return '';
    }

    return mb_substr($text, 0, 240).(mb_strlen($text) > 240 ? '…' : '');
}

$rootXml = fetchUrl(ROOT_SITEMAP);
if ($rootXml === null) {
    fwrite(STDERR, "Could not load root sitemap.\n");
    exit(1);
}

$sitemaps = extractSitemapUrls($rootXml);
$pageUrls = [];

foreach ($sitemaps as $sitemapUrl) {
    $xml = fetchUrl($sitemapUrl);
    if ($xml === null) {
        continue;
    }

    foreach (extractSitemapUrls($xml) as $url) {
        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host) || !str_ends_with($host, 'focusagency.ro')) {
            continue;
        }
        $pageUrls[] = $url;
    }
}

$pageUrls = array_values(array_unique($pageUrls));
sort($pageUrls);

$pagesByPath = [];
$total = count($pageUrls);

foreach ($pageUrls as $index => $pageUrl) {
    $html = fetchUrl($pageUrl);
    if ($html === null) {
        continue;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
    libxml_clear_errors();

    if ($loaded === false) {
        continue;
    }

    $contentNode = pickContentNode($dom);
    sanitizeNode($contentNode, $dom);

    $path = normalizePath($pageUrl);
    $title = extractTitle($dom, $contentNode);
    $sections = extractSections($contentNode, $dom, $title);
    $excerpt = excerptFromSections($sections);
    $imagePlaceholders = extractImagePlaceholders($contentNode, $dom);

    $pagesByPath[$path] = [
        'path' => $path,
        'source_url' => $pageUrl,
        'title' => $title,
        'excerpt' => $excerpt,
        'sections' => $sections,
        'image_placeholders' => $imagePlaceholders,
    ];

    echo sprintf("[%d/%d] Imported %s\n", $index + 1, $total, $path);
}

ksort($pagesByPath);

$payload = [
    'generated_at' => gmdate('c'),
    'source_sitemap' => ROOT_SITEMAP,
    'sitemaps' => $sitemaps,
    'page_count' => count($pagesByPath),
    'pages' => array_values($pagesByPath),
];

$outputDir = dirname(OUTPUT_FILE);
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0775, true);
}

file_put_contents(
    OUTPUT_FILE,
    json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);

echo sprintf("Saved %d pages to %s\n", count($pagesByPath), OUTPUT_FILE);
