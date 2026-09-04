<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

$syliusAdminPassword = trim((string) (getenv('SYLIUS_ADMIN_PASSWORD') ?: $_ENV['SYLIUS_ADMIN_PASSWORD'] ?? ''));

if ('' === $syliusAdminPassword) {
    throw new \RuntimeException('SYLIUS_ADMIN_PASSWORD must be set in the environment before loading app fixtures.');
}

return App::config([
    'sylius_fixtures' => [
        'suites' => [
            'app' => [
                'fixtures' => [
                    'admin_user' => [
                        'options' => [
                            'custom' => [
                                'admin' => [
                                    'email' => 'sylius@example.com',
                                    'username' => 'sylius',
                                    'password' => $syliusAdminPassword,
                                    'locale_code' => 'en_US',
                                    'avatar' => '@SyliusCoreBundle/Resources/fixtures/adminAvatars/luke.webp',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
]);
