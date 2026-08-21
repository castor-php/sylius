<?php

namespace Castor\Sylius\Import;

/**
 * Normalise une URL ou un hostname d'import en base URL + host.
 *
 * Accepte notamment :
 * - https://www.example.com
 * - http://www.example.com
 * - www.example.com
 * - example.com
 * - //www.example.com
 *
 * @return array{base_url: string, host: string}
 */
function parse_import_site_input(string $input): array
{
    $input = trim($input);
    $input = rtrim($input, '/');

    if ('' === $input) {
        throw new \InvalidArgumentException('Site URL or host is required.');
    }

    $candidate = $input;

    if (str_starts_with($candidate, '//')) {
        $candidate = 'https:' . $candidate;
    } elseif (!preg_match('#^https?://#i', $candidate)) {
        $candidate = 'https://' . $candidate;
    }

    $parts = parse_url($candidate);

    if (!\is_array($parts) || !isset($parts['host']) || '' === $parts['host']) {
        throw new \InvalidArgumentException(\sprintf('Invalid site URL or host "%s".', $input));
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));

    if (!\in_array($scheme, ['http', 'https'], true)) {
        throw new \InvalidArgumentException('URL scheme must be http or https.');
    }

    $host = $parts['host'];
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';
    $baseUrl = $scheme . '://' . $host . $port;

    if (!filter_var($baseUrl, \FILTER_VALIDATE_URL)) {
        throw new \InvalidArgumentException(\sprintf('Invalid site URL or host "%s".', $input));
    }

    return [
        'base_url' => $baseUrl,
        'host' => $host,
    ];
}

function normalize_base_url(string $urlOrHost): string
{
    return parse_import_site_input($urlOrHost)['base_url'];
}

function normalize_import_host(string $urlOrHost): string
{
    return parse_import_site_input($urlOrHost)['host'];
}
