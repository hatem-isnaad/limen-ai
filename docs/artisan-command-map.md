# Artisan Command Map

All commands registered under `limen-ai:*` namespace.

## Installation & Maintenance

| Command | Purpose |
|---------|---------|
| `limen-ai:install` | Publish config, env example, views, assets, and stubs |
| `limen-ai:doctor` | Validate config, providers, queue, broadcasting |
| `limen-ai:validate` | Validate agents, tools, skills, workflows definitions |

## Generators

| Command | Generates |
|---------|-----------|
| `limen-ai:make:agent` | Agent config stub |
| `limen-ai:make:tool` | Tool class + optional test |
| `limen-ai:make:skill` | Skill config stub |
| `limen-ai:make:workflow` | Workflow config stub |
| `limen-ai:make:connector` | HTTP connector config stub |
| `limen-ai:make:provider` | Custom LLM provider adapter |
| `limen-ai:make:memory` | Memory store implementation |
| `limen-ai:make:knowledge` | Knowledge retriever implementation |

## Inspection

| Command | Purpose |
|---------|---------|
| `limen-ai:list` | Summary of registered components |
| `limen-ai:agents` | List agents |
| `limen-ai:tools` | List tools |
| `limen-ai:skills` | List skills |
| `limen-ai:workflows` | List workflows |
| `limen-ai:logs` | Show buffered audit log entries |

## Testing & Execution

| Command | Purpose |
|---------|---------|
| `limen-ai:agent:test {agent}` | Run agent against fake or live provider |
| `limen-ai:tool:test {tool}` | Execute tool with JSON input |
| `limen-ai:workflow:test {workflow}` | Dry-run workflow |
| `limen-ai:run {agent}` | Interactive CLI chat session |

## Install Options

```bash
php artisan limen-ai:install
php artisan limen-ai:install --force  # overwrite publishables
```

## Stub Publishing

```bash
php artisan vendor:publish --tag=limen-ai-stubs
```

## Facade

```php
use LimenAi\Facades\LimenAi;

$runId = LimenAi::run('example', $conversationId, 'Hello', ['user_id' => 1]);
```
