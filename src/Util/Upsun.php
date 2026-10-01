<?php

declare(strict_types=1);

namespace Castor\Sylius\Util;

use Castor\Sylius\App;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

final readonly class Upsun
{
    /**
     * @return mixed
     */
    public static function parseConfig(App $app): mixed
    {
        $file = $app->directory() . '/.upsun/config.yaml';
        $contents = file_get_contents($file);

        if (false === $contents) {
            throw new \RuntimeException(\sprintf('Could not read "%s".', $file));
        }

        // Upsun uses explicit scalar keys for regex rules, which Symfony YAML does not parse.
        $contents = preg_replace_callback(
            '/^(?<indent>[ \t]*)\? (?<key>\x27[^\x27]*\x27)\R(?P=indent):[ \t]*(?<value>.+)$/m',
            static fn(array $matches): string => $matches['indent'] . $matches['key'] . ":\n" . $matches['indent'] . '  ' . $matches['value'],
            $contents,
        );

        if (null === $contents) {
            throw new \RuntimeException('Could not normalize Upsun YAML mapping keys.');
        }

        return SymfonyYaml::parse($contents, SymfonyYaml::PARSE_CUSTOM_TAGS);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function databaseEngine(array $config): ?string
    {
        $services = $config['services'] ?? [];

        if (!\is_array($services)) {
            return null;
        }

        $engines = [];

        foreach ($services as $serviceName => $service) {
            if (!\is_string($serviceName) || !\is_array($service) || !\is_string($service['type'] ?? null)) {
                continue;
            }

            $engine = self::engineFromType($service['type']);

            if (null !== $engine) {
                $engines[$serviceName] = $engine;
            }
        }

        if ([] === $engines) {
            return null;
        }

        $relatedServices = [];

        foreach ($config['applications'] as $application) {
            $relationships = \is_array($application) ? ($application['relationships'] ?? []) : [];

            if (!\is_array($relationships)) {
                continue;
            }

            foreach ($relationships as $relationship) {
                if (!\is_string($relationship)) {
                    continue;
                }

                $serviceName = explode(':', $relationship, 2)[0];

                if (isset($engines[$serviceName])) {
                    $relatedServices[$serviceName] = $engines[$serviceName];
                }
            }
        }

        $candidateEngines = [] !== $relatedServices ? $relatedServices : $engines;
        $distinctEngines = array_unique(array_values($candidateEngines));

        return 1 === \count($distinctEngines) ? reset($distinctEngines) : null;
    }

    private static function engineFromType(string $type): ?string
    {
        if (1 === preg_match('/^(?:mysql|mariadb):/i', $type)) {
            return 'MySQL';
        }

        if (1 === preg_match('/^(?:postgres|postgresql):/i', $type)) {
            return 'PostgreSQL';
        }

        return null;
    }
}
