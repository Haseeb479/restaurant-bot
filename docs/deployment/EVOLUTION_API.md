# Evolution API Deployment & Version Management Guide

This document outlines the deployment topology, security isolation, version pinning policy, and upgrade/rollback procedures for the Evolution API service within the Foodio WhatsApp Ordering SaaS platform (Requirements 14 & 15).

---

## 1. Network Topology & Private Network Deployment (Req 14)

### Security Requirements
- **Zero Public Exposure**: The Evolution API service must **never** expose ports (e.g. `8080`) to public interfaces (`0.0.0.0`) on host systems.
- **Strict Network Segregation**:
  - `public_net`: Ingress network where only the Foodio Web App (`app`) is exposed on standard HTTP/HTTPS ports (`8080` / `443`).
  - `internal_net`: Isolated bridge network (`internal: true`) connecting the application container, `mysql`, `redis`, and `evolution-api`. No direct inbound or egress routing from the outside world.
- **Internal Access Only**:
  - The Laravel application communicates with Evolution API strictly through the internal service name:
    ```env
    EVOLUTION_BASE_URL=http://evolution-api:8080
    ```
  - Evolution API instances store session secrets and tokens internally without exposing administrative panels or raw instance APIs to the internet.

### Network Diagram

```
[ Public Internet / Customers ]
            │
            ▼ (Port 80/443/8080)
┌───────────────────────────────────────┐
│              app (Foodio)             │
│  - Laravel / PHP 8.4-FPM              │
│  - Nginx Reverse Proxy                │
│  - Webhook Controller (Validated)     │
└──────────────────┬────────────────────┘
                   │
  [ internal_net (isolated bridge, no public ports) ]
                   │
    ┌──────────────┼──────────────┬──────────────┐
    ▼              ▼              ▼              ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│evolution-api │ │    mysql     │ │    redis     │ │ (Worker)     │
│:8080 (int)   │ │:3306 (int)   │ │:6379 (int)   │ │queue:work    │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘
```

---

## 2. Version Pinning Policy (Req 15)

### Pinned Release
- **Current Production Image**: `atendai/evolution-api:v2.2.3`
- **Floating Tags Prohibited**: Tags such as `:latest`, `:main`, or `:v2` are strictly disallowed in production configurations to prevent unintended breaking changes, schema regressions, or upstream vulnerabilities.

### Evolution API Compatibility Matrix
| Foodio SaaS Version | Evolution API Version | Status | Notes |
|---|---|---|---|
| `v1.0.x` | `atendai/evolution-api:v2.2.3` | **Certified Production** | Multi-tenant Webhook v2, QR polling, SSE stability |

---

## 3. Upgrade Procedure

Follow this disciplined 6-step procedure when upgrading Evolution API versions:

### Step 1: Upstream Release Review
- Review upstream Evolution API GitHub release notes and changelogs.
- Verify whether the target version introduces breaking changes in:
  - Webhook payload structure (`messages.upsert`, `connection.update`).
  - Instance creation API endpoints (`/instance/create`, `/instance/connect`).
  - Redis cache schema or internal SQLite/Postgres storage formats.

### Step 2: Backup Persistent Storage
Before altering container definitions, trigger a database backup and snapshot persistent volumes:
```bash
# 1. Run database backup command
php artisan backup:database

# 2. Snapshot Docker volumes
docker run --rm -v foodio_evolution_instances:/data -v $(pwd)/storage/app/private/backups:/backup \
    alpine tar czf /backup/evolution_instances_pre_upgrade.tar.gz /data
```

### Step 3: Staging Verification
Deploy the candidate image tag to an isolated staging environment:
```bash
# Test connection and instance creation
curl -s -H "apikey: $EVOLUTION_API_KEY" http://localhost:8080/instance/fetchInstances
```

### Step 4: Update Pinned Tag
Update the image specification in `docker-compose.yml`:
```yaml
  evolution-api:
    image: atendai/evolution-api:v2.2.x # <-- Update to verified target version
```

### Step 5: Rolling Container Recreation
```bash
# Pull new image without downtime
docker compose pull evolution-api

# Recreate evolution-api container
docker compose up -d --no-deps evolution-api
```

### Step 6: Health Check & Verification
Verify service availability and health:
```bash
# Check container logs
docker compose logs -f --tail=100 evolution-api

# Check Foodio application readiness check
curl -f http://127.0.0.1:8080/health/ready
```

---

## 4. Rollback Plan

If an upgraded version exhibits regressions, connection dropouts, or webhook delivery failures:

1. **Revert Image Tag in `docker-compose.yml`**:
   ```yaml
   image: atendai/evolution-api:v2.2.3
   ```
2. **Re-apply Container**:
   ```bash
   docker compose up -d --no-deps evolution-api
   ```
3. **Restore Instance Storage (if required)**:
   ```bash
   docker compose stop evolution-api
   docker run --rm -v foodio_evolution_instances:/data -v $(pwd)/storage/app/private/backups:/backup \
       alpine sh -c "rm -rf /data/* && tar xzf /backup/evolution_instances_pre_upgrade.tar.gz -C /"
   docker compose start evolution-api
   ```
4. **Verify Application Readiness**:
   ```bash
   curl -f http://127.0.0.1:8080/health/ready
   ```
