---
paths:
  - 'database/migrations/**'
---

# Migrations

## PostgreSQL enum creation must use the DO/IF NOT EXISTS pattern
Never use bare `CREATE TYPE foo AS ENUM (...)`. Use the project-standard idempotent form: `DB::statement("DO \$\$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'foo') THEN CREATE TYPE foo AS ENUM (...); END IF; END \$\$");`. Bare CREATE TYPE fails on repeated `migrate:fresh` runs in the testing database because PostgreSQL has no `CREATE TYPE IF NOT EXISTS` syntax.
