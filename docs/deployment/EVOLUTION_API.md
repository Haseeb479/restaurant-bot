# Evolution API on Railway

The production Laravel application uses Evolution API for WhatsApp. Deploy the
application and Evolution API as separate Railway services and connect them over
Railway private networking.

## Service configuration

- Keep Evolution API's management/API service private. Do not enable public
  ingress unless it is specifically required and access-controlled.
- Configure the Laravel service's `EVOLUTION_BASE_URL` with Evolution API's
  private Railway address, including its port.
- Set `EVOLUTION_API_KEY` in both services to the same unique secret. Store it
  in Railway variables; do not commit it or include it in support logs.
- Persist Evolution API's instance/session data using storage supported by its
  service deployment. Confirm the data survives a service restart and redeploy.
- Use a stable, version-pinned Evolution API release and review its release
  notes before upgrading.

## Webhooks

Evolution API must be able to reach the Laravel application's HTTPS webhook
endpoint. The Laravel app accepts Evolution events at:

```text
POST /webhook/whatsapp
```

Configure the webhook against the application's public HTTPS URL and the same
Evolution API key used by Laravel. Do not expose the Evolution API management
endpoint publicly just to make webhook delivery work; webhook traffic flows
from Evolution API to the application.

The application routes each event to the restaurant associated with its
Evolution instance. Initialize instances from the application environment with:

```bash
php artisan evolution:init
```

To initialize one restaurant only:

```bash
php artisan evolution:init <restaurant-id>
```

Review command output and Evolution API service logs if instance creation or
webhook registration fails. Avoid sharing API keys, QR codes, pairing codes, or
customer message payloads in logs or support requests.

## Upgrade and recovery

1. Back up PostgreSQL and verify that Evolution API session storage is
   persistent.
2. Review upstream breaking changes to event payloads, instance management,
   and the Evolution API storage format.
3. Test the candidate release with a staging app and Evolution API service.
4. Update the Evolution API service to the pinned candidate release in Railway.
5. Verify QR retrieval, connection-state updates, inbound messages, replies, and
   order persistence.
6. If the release regresses, redeploy the previous pinned version and restore
   service data only when required by the upgrade.

See [`DEPLOYMENT.md`](DEPLOYMENT.md) for the Railway application checklist.
