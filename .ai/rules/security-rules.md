# Security Rules

1. Derive user/tenant context from Laravel auth only
2. Never pass secrets to LLM prompts
3. Redact sensitive data in logs
4. SSRF protection for HTTP tools
5. Idempotency for side-effect tools
6. Execution limits on every run
7. Human approval for sensitive operations
