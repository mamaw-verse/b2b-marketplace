#!/usr/bin/env bash
set -euo pipefail

APP_NAME="One Halal Global"
ENVIRONMENT="production"

echo "🚀 Deploying ${APP_NAME} to ${ENVIRONMENT}..."

# Trigger the deployment
if ! cloud deploy "${APP_NAME}" "${ENVIRONMENT}" -n; then
    echo "❌ Deploy command failed." >&2
    echo "   Run: cloud deployment:list --json -n" >&2
    exit 1
fi

echo "📡 Monitoring deployment..."

# Monitor blocks until deploy completes; exits non-zero on failure
if ! cloud deploy:monitor -n; then
    echo "❌ Deployment failed." >&2
    echo "   Run: cloud deployment:get --json -n" >&2
    exit 1
fi

echo "✅ Deployment to ${ENVIRONMENT} succeeded."
