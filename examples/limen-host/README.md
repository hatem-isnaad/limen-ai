# Limen Host Integration (Reference)

This folder shows how the **Limen 3PL** host application wires business tools into the generic `limen-ai/limen-ai` package.

Copy these files into your Laravel host app and merge the config snippet into `config/limen-ai.php`.

## Layout

```
app/
├── Contracts/ShipmentService.php
├── Services/ShipmentService.php
└── LimenAi/Tools/
    ├── GetShipmentStatus.php
    └── SendCustomerMessage.php
```

## Install

1. Publish package config and stubs:

```bash
php artisan vendor:publish --tag=limen-ai-config
php artisan vendor:publish --tag=limen-ai-limen-demo
```

2. Copy the example classes from this directory into `app/` (or generate from published stubs under `stubs/limen-ai/limen/`).

3. Merge `config/limen-ai.limen.php` into your host `config/limen-ai.php`.

4. Bind your real `ShipmentService` in a service provider:

```php
$this->app->singleton(
    \App\Contracts\ShipmentService::class,
    \App\Services\ShipmentService::class,
);
```

5. Register tool classes in config:

```php
'tools' => [
    'get_shipment_status' => [
        'class' => \App\LimenAi\Tools\GetShipmentStatus::class,
        // ...
    ],
    'send_customer_message' => [
        'class' => \App\LimenAi\Tools\SendCustomerMessage::class,
        'confirmation' => true,
        // ...
    ],
],
```

6. Add the chat UI to a Blade layout:

```blade
<x-limen-ai::widget agent="limen_3pl" />
```

7. Copy the host integration test template and adapt it in your app:

```
examples/limen-host/tests/Feature/LimenAiAgentTest.php
```

Set `LIMEN_AI_PERSISTENCE_DRIVER=database`, run migrations, then use the template to verify conversation + message HTTP flows against your configured agent.

## Demo Scenarios

| Scenario | Agent flow |
|----------|------------|
| Shipment lookup | User asks for shipment `12345` → LLM calls `get_shipment_status` → Laravel authorizes → service returns status |
| Delay notification | Agent drafts message → `send_customer_message` pauses for approval → user approves → message sent + audited |

See [docs/limen-integration.md](../../docs/limen-integration.md) for the full integration guide.
