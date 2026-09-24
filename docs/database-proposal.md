# Database Proposal

Agent/Tool/Skill definitions start in config. The database stores **runtime and operational data**.

## Tables

### limen_ai_conversations

| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | |
| agent_key | string | References config agent key |
| user_id | nullable unsignedBigInteger | Host user |
| guest_token | nullable string | Optional guest sessions |
| title | nullable string | |
| metadata | json | |
| state | string | active, archived, waiting_approval |
| created_at / updated_at | timestamps | |

Indexes: `user_id`, `agent_key`, `state`

### limen_ai_messages

| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | |
| conversation_id | uuid FK | |
| role | string | user, assistant, tool, system |
| content | longText | |
| structured_content | json nullable | |
| metadata | json | |
| created_at | timestamp | |

Indexes: `conversation_id`, `created_at`

### limen_ai_runs

| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | |
| conversation_id | uuid FK | |
| agent_key | string | |
| user_id | nullable unsignedBigInteger | |
| status | string | pending, running, waiting_approval, completed, failed, cancelled |
| current_step | unsignedInteger | |
| max_steps | unsignedInteger | |
| error_code | nullable string | |
| error_message | nullable text | |
| started_at / finished_at | timestamps nullable | |
| metadata | json | |

Indexes: `conversation_id`, `status`, `user_id`

### limen_ai_run_steps

| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | |
| run_id | uuid FK | |
| step_number | unsignedInteger | |
| type | string | llm, tool, approval, workflow |
| payload | json | |
| result | json nullable | |
| status | string | |
| created_at | timestamp | |

Indexes: `run_id`, `step_number`

### limen_ai_tool_executions

| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | |
| run_id | uuid FK | |
| tool_key | string | |
| input | json | redacted copy |
| output | json nullable | redacted copy |
| status | string | |
| duration_ms | unsignedInteger nullable | |
| idempotency_key | nullable string unique | |
| created_at | timestamp | |

Indexes: `run_id`, `tool_key`, `idempotency_key`

### limen_ai_approvals

| Column | Type | Notes |
|--------|------|-------|
| id | uuid PK | |
| run_id | uuid FK | |
| tool_key | string | |
| payload | json | |
| status | string | pending, approved, rejected, expired |
| requested_by | unsignedBigInteger nullable | |
| resolved_by | unsignedBigInteger nullable | |
| resolved_at | timestamp nullable | |
| created_at / updated_at | timestamps | |

Indexes: `run_id`, `status`

### limen_ai_audit_logs

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| actor_id | nullable unsignedBigInteger | |
| agent_key | nullable string | |
| action | string | |
| subject_type | nullable string | |
| subject_id | nullable string | |
| context | json | redacted |
| created_at | timestamp | |

Indexes: `actor_id`, `action`, `created_at`

### limen_ai_usage_records

| Column | Type | Notes |
|--------|------|-------|
| id | bigIncrements | |
| run_id | uuid nullable | |
| provider | string | |
| model | string | |
| prompt_tokens | unsignedInteger | |
| completion_tokens | unsignedInteger | |
| estimated_cost | decimal(10,6) nullable | |
| created_at | timestamp | |

## Future SaaS Tables (not v1)

- `limen_ai_organizations`
- `limen_ai_tenants`
- `limen_ai_agent_definitions` (DB-backed agents)
- `limen_ai_tool_definitions`
- `limen_ai_api_keys`
- `limen_ai_subscriptions`

## Multi-Tenancy Preparation

- All operational tables include optional `tenant_id` column (nullable in v1)
- Host app responsible for global scopes; package provides hook interface
