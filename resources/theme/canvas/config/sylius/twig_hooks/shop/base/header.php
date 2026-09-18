<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'sylius_twig_hooks' => [
        'hooks' => [
            'sylius_shop.base.footer' => [
                'content' => [
                    'template' => 'shop/shared/layout/base/footer/content.html.twig',
                ],
            ],
        ],
    ],
]);
