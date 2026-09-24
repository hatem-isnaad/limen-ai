# Artisan Command Map

All commands registered under `limen-ai:*` namespace.

## Installation & Maintenance

| Command | Purpose |
|---------|---------|
| `limen-ai:install` | Publish config, views, assets, migrations, examples |
| `limen-ai:doctor` | Validate config, providers, queue, broadcasting |
| `limen-ai:validate` | Validate agents, tools, skills, workflows definitions |

## Generators

| Command | Generates |
|---------|-----------|
| `limen-ai:make:agent` | Agent config stub / class |
| `limen-ai:make:tool` | Tool class + test |
| `limen-ai:make:skill` | Skill class |
| `limen-ai:make:workflow` | Workflow definition class |
| `limen-ai:make:connector` | HTTP connection config |
| `limen-ai:make:provider` | Custom LLM provider adapter |
| `limen-ai:make:memory` | Memory store implementation |
| `limen-ai:make:knowledge` | Knowledge source handler |

## Inspection

| Command | Purpose |
|---------|---------|
| `limen-ai:list` | Summary of registered components |
| `limen-ai:agents` | List agents |
| `limen-ai:tools` | List tools |
| `limen-ai:skills` | List skills |
| `limen-ai:workflows` | List workflows |
| `limen-ai:logs` | Tail/filter audit and run logs |

## Testing & Execution

| Command | Purpose |
|---------|---------|
| `limen-ai:agent:test {agent}` | Run agent against fake or live provider |
| `limen-ai:tool:test {tool}` | Execute tool with sample input |
| `limen-ai:workflow:test {workflow}` | Dry-run workflow |
| `limen-ai:run {agent}` | Interactive CLI chat session |

## Install Options

```
php artisan limen-ai:install
php artisan limen-ai:install --force  # overwrite publishables with confirmation
```

## Stub Publishing

```
php artisan vendor:publish --tag=limen-ai-stubs
```
