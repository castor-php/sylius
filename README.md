# Castor Sylius Plugin

A [Castor](https://castor.jolicode.com/) plugin that turns a PHP description of
your stack into a Sylius app, and gives you the tasks to drive it.

## Installation

```bash
castor composer require castor-php/sylius
```

## Usage

1. Create a `castor.php` file in your project root:

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
            ->withDockerfile(__DIR__ . '/infrastructure/docker/php/Dockerfile')
            ->withDatabaseService($postgresService)
            ->withDomain('app.test')
            ->withHttpAccess()
    );
}
```

## 🦫 Available commands

### Add or Remove plugins

#### ✚ Add plugins

A single command to add all the Sylius plugins you need.

**Example:**

```shell
castor sylius:add cms invoicing refund
```

#### Available plugins

| Plugin          | Description                           |
|-----------------|---------------------------------------|
| bugsnag         | Add the Symfony BugSnag plugin        |
| cms             | Add the Sylius CMS plugin             |
| gdpr            | Add the Synolia GDPR plugin           |
| invoicing       | Add the Sylius Invoicing plugin       |
| media           | Add the Jolicode Media plugin         |
| paypal          | Add the Sylius Paypal plugin          |
| refund          | Add the Sylius Refund plugin          |
| stripe          | Add the Sylius Stripe plugin          |
| wishlist        | Add the Sylius Wishlist plugin        |

#### ❌ Remove plugins

A single command to remove the Sylius plugins you do not need.

**Example:**

```shell
castor sylius:remove mollie paypal
```

#### Available plugins

| Plugin    | Description                                          |
|-----------|------------------------------------------------------|
| bugsnag   | Remove the Symfony BugSnag plugin                    |
| cms       | Remove the Sylius CMS plugin                         |
| gdpr      | Remove the Synolia GDPR plugin                       |
| invoicing | Remove the Sylius Invoicing plugin                   |
| paypal    | Remove the Sylius Paypal plugin                      |
| stripe    | Remove the Sylius Stripe plugin                      |
| wishlist  | Remove the Sylius Wishlist plugin                    |

## License

This plugin is part of the Castor project, released under the MIT license.
