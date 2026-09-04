# Castor Sylius Plugin

A [Castor](https://castor.jolicode.com/) plugin that turns a PHP description of
your stack into a Sylius app, and gives you the tasks to drive it.

## Installation

1. Create a `castor.php` file in your project root:

2. Create a composer file for Castor:

```shell
echo '{}' > castor.composer.json
```

3. Install the Castor plugin for Sylius

```shell
castor composer require castor-php/sylius "@dev"
```

4. Setup a new Sylius application
```shell
castor docker:service:install sylius
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

| Plugin         | Description                          |
|----------------|--------------------------------------|
| bugsnag        | Add the Symfony BugSnag plugin       |
| cms            | Add the Sylius CMS plugin            |
| gdpr           | Add the Synolia GDPR plugin          |
| invoicing      | Add the Sylius Invoicing plugin      |
| media          | Add the Jolicode Media plugin        |
| paypal         | Add the Sylius Paypal plugin         |
| product_bundle | Add the Sylius Product Bundle plugin |
| refund         | Add the Sylius Refund plugin         |
| stripe         | Add the Sylius Stripe plugin         |
| wishlist       | Add the Sylius Wishlist plugin       |

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
| mollie    | Remove the Sylius Mollie Plugin                      |
| payments  | Remove all payment plugins (Mollie, Paypal & Stripe) |
| paypal    | Remove the Sylius Paypal plugin                      |
| stripe    | Remove the Sylius Stripe plugin                      |
| wishlist  | Remove the Sylius Wishlist plugin                    |

### ☰ Remove menu items from the Admin panel

Clean up the Sylius Admin panel by removing menu items you don't need.

This command generates `application/src/Menu/Admin/RemoveMenuItemsListener.php`, a Symfony
event listener that hides matching items from the admin main menu. Subsequent runs
automatically merge new items into the existing list without prompting.

Pass one or more menu item names as arguments. Use `parent/child` syntax for sub-items.

**Example:**

```shell
castor sylius:menu:remove official_support sylius.ui.administration
castor sylius:menu:remove marketing/product_reviews
```

#### Available options

| Option    | Shortcut | Description                                                                        |
|-----------|----------|------------------------------------------------------------------------------------|
| --replace | -r       | Replace the full removed-items list instead of merging with existing items         |
| --restore | -b       | Restore previously removed menu items; deletes the listener when the list is empty |

**Replace the removed-items list:**

```shell
castor sylius:menu:remove customers orders --replace
```

**Restore previously removed items:**

```shell
castor sylius:menu:remove --restore customers
castor sylius:menu:remove -b customers orders
```

**Example workflow:**

```shell
# Hide customers and orders from the admin menu
castor sylius:menu:remove customers orders

# Later, hide product reviews too (auto-merged)
castor sylius:menu:remove marketing/product_reviews

# Restore the customers menu item
castor sylius:menu:remove -b customers
```

#### Default menu items

Top-level items:

| Name                     | Description      |
|--------------------------|------------------|
| dashboard                | Dashboard        |
| catalog                  | Catalog          |
| sales                    | Sales            |
| customers                | Customers        |
| marketing                | Marketing        |
| configuration            | Configuration    |
| official_support         | Official Support |
| sylius.ui.administration | Administration   |

Sub-items (use `parent/child` syntax):

| Name                                   | Description           |
|----------------------------------------|-----------------------|
| catalog/taxons                         | Taxons                |
| catalog/products                       | Products              |
| catalog/inventory                      | Inventory             |
| catalog/attributes                     | Attributes            |
| catalog/options                        | Options               |
| catalog/association_types              | Association Types     |
| sales/orders                           | Orders                |
| sales/payments                         | Payments              |
| sales/shipments                        | Shipments             |
| customers/customers                    | Customers             |
| customers/groups                       | Groups                |
| marketing/promotions                   | Promotions            |
| marketing/catalog_promotions           | Catalog Promotions    |
| marketing/product_reviews              | Product Reviews       |
| configuration/channels                 | Channels              |
| configuration/countries                | Countries             |
| configuration/zones                    | Zones                 |
| configuration/currencies               | Currencies            |
| configuration/exchange_rates           | Exchange Rates        |
| configuration/locales                  | Locales               |
| configuration/payment_methods          | Payment Methods       |
| configuration/shipping_methods         | Shipping Methods      |
| configuration/shipping_categories      | Shipping Categories   |
| configuration/tax_categories           | Tax Categories        |
| configuration/tax_rates                | Tax Rates             |
| configuration/admin_users              | Admin Users           |
| official_support/sylius_plus           | Sylius Plus           |
| official_support/browse_plugins        | Browse Plugins        |
| official_support/professional_services | Professional Services |
| official_support/find_a_partner        | Find a Partner        |
| official_support/sylius_certification  | Sylius Certification  |
| sylius.ui.administration/roles         | Roles                 |

The package autoloads via Composer. Do **not** `import('composer://castor-php/sylius')` — that would load the package's local `castor.php` and conflict with your own context. Register `SyliusService` as shown above to expose all tasks (`sylius:*`, `app:*`, `sylius:import:*`).

## E-commerce import

Import products, collections, images and prices from an **AI-generated catalog**, or load YAML produced by an external fetch step into Sylius fixtures.

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
| `sylius:import:ai:build`                          | AI description → YAML catalog          |
| `sylius:import:fixtures:generate existing\|ai`    | YAML → PHP fixtures + images           |
| `sylius:import:fixtures:load`                     | Load `import` fixture suite            |

#### Workflow — existing site

Requires import YAML under `.castor/import/var/{project-slug}/` (products + collections). If you use the private `castor-php/sylius-import-fetch` plugin, run `sylius:import:existing:fetch` first; otherwise prepare the YAML yourself.

```bash
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
