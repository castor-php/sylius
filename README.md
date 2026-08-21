# Castor Sylius Plugin

A [Castor](https://castor.jolicode.com/) plugin that turns a PHP description of
your stack into a Sylius app, and gives you the tasks to drive it.

## Installation

```bash
castor composer require castor-php/sylius
```

## Usage

1. Require the package in your Castor project:

```bash
castor composer require castor-php/sylius
```

2. Configure your stack in `castor.php`:

```php
<?php

use Castor\Attribute\AsContext;
use Castor\Attribute\AsListener;
use Castor\Context;
use Castor\Docker\Event\RegisterServiceEvent;
use Castor\Docker\Service\PostgresService;
use Castor\Sylius\Service\SyliusService;

#[AsContext(default: true)]
function default_context(): Context
{
    return new Context([
        'root_domain' => 'app.test',
    ]);
}

#[AsListener(RegisterServiceEvent::class)]
function register_service(RegisterServiceEvent $event): void
{
    $postgresService = new PostgresService();
    $event->addService($postgresService);

    $event->addService(
        (new SyliusService(name: 'app'))
            ->withDirectory(__DIR__ . '/app')
            ->withDatabaseService($postgresService)
            ->withDomain('app.test')
            ->withHttpAccess()
    );
}
```

The package autoloads via Composer. Do **not** `import('composer://castor-php/sylius')` — that would load the package's local `castor.php` and conflict with your own context. Register `SyliusService` as shown above to expose all tasks (`sylius:*`, `app:*`, `sylius:import:*`).

## E-commerce import

Import products, collections, images and prices from an **existing online store** or an **AI-generated catalog**.

#### Prerequisites

1. Create the Sylius app and start the stack:

```bash
composer create-project sylius/sylius-standard app
castor build && castor up
castor app:db:migrate
castor app:db:fixtures app
```

2. Configure AI in `.castor/.env` (created automatically on first import command). Copy from `.castor/.env.example`.

| Variable         | Default                  | Description                             |
|------------------|--------------------------|-----------------------------------------|
| `AI_PROVIDER`    | `openrouter`             | `ollama` (local) or `openrouter`        |
| `AI_MODEL`       | provider-specific        | Text / structured-output model          |
| `AI_IMAGE_MODEL` | provider-specific        | Image generation model (AI import only) |
| `AI_BASE_URL`    | `http://127.0.0.1:11434` | Ollama URL (ignored for OpenRouter)     |
| `AI_API_KEY`     | —                        | Required when `AI_PROVIDER=openrouter`  |

#### Commands

| Command                                           | Role                                   |
|---------------------------------------------------|----------------------------------------|
| `sylius:import:existing:fetch`                    | Fetch sitemap → YAML                   |
| `sylius:import:ai:build`                          | AI description → YAML catalog          |
| `sylius:import:fixtures:generate existing\|ai`    | YAML → PHP fixtures + images           |
| `sylius:import:fixtures:load`                     | Load `import` fixture suite            |

#### Workflow — existing site

```bash
castor sylius:import:existing:fetch https://www.example.store --name="Example" --description="Fashion"
castor sylius:import:fixtures:generate existing --project=example --limit=100
castor sylius:import:fixtures:load --project=example
```

#### Workflow — AI-generated catalog

```bash
castor sylius:import:ai:build --name="My Store" --description="Organic kids clothing boutique"
castor sylius:import:fixtures:generate ai --project=my-store
castor sylius:import:fixtures:load --project=my-store
```

Import data is stored per project slug under `.castor/import/var/{project-slug}/`.

See [agents.md](agents.md) for detailed agent guidelines.

## License

This plugin is part of the Castor project, released under the MIT license.
