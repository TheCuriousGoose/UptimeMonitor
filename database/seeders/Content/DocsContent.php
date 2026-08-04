<?php

namespace Database\Seeders\Content;

/**
 * Starting documentation. Written against what the application actually does,
 * so it stays true as long as the behaviour does.
 */
class DocsContent
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function entries(): array
    {
        return [
            [
                'slug' => 'getting-started',
                'title' => 'Getting started',
                'category' => 'Basics',
                'sort_order' => 1,
                'excerpt' => 'Create your first monitor and get alerted when it fails.',
                'body' => <<<'MD'
Vigil Watch checks the systems you nominate on a schedule and tells you when
one stops responding. This page takes you from an empty account to a working
alert.

## 1. Create a monitor

Open **Monitors → New monitor**. Pick the kind of check you need — for a
website or JSON API, that is **Website / API**.

Fill in:

- **Name** — how it appears in lists and alerts.
- **URL** — the full address to check, including `https://`.
- **Check every** — how often to run it. Thirty seconds is the minimum.
- **Timeout** — how long to wait before treating the check as failed.

Save, and the first check runs immediately.

## 2. Add somewhere to be alerted

A monitor with no notification channel records failures but tells nobody.

Open **Alerts → Add channel**, choose **Email**, and enter an address. Use
**Send test alert** from the row menu to confirm it arrives before you rely on
it.

If your team runs on-call rotations, use **Integrations** instead — PagerDuty,
Opsgenie and Microsoft Teams are set up there, and the first two resolve the
alert automatically when the monitor recovers.

## 3. Attach the channel to the monitor

Edit the monitor and tick the channel under **Alerts**. Channels are opt-in per
monitor, so a noisy staging check need not page the same people as production.

## 4. Confirm it works

Open the monitor and press **Check now**. The uptime timeline and response
chart fill in as results arrive.

To see a real failure end to end, point a throwaway monitor at a URL that
returns an error — `https://httpstat.us/503` works — and watch it open an
incident once it crosses the confirmation threshold.

## Next steps

- [Check types](/docs/check-types) — what each kind of check actually does.
- [Alerting and incidents](/docs/alerting-and-incidents) — how a failure
  becomes an incident and who hears about it.
- [Using the API](/docs/using-the-api) — manage monitors from CI instead of a
  browser.
MD,
            ],
            [
                'slug' => 'check-types',
                'title' => 'Check types',
                'category' => 'Basics',
                'sort_order' => 2,
                'excerpt' => 'The six kinds of check and when to reach for each.',
                'body' => <<<'MD'
Every monitor has a type, which decides what a successful check means.

## Website / API

Issues an HTTP request and treats a successful status code as up.

- **HTTP method** — `GET`, `POST` or `HEAD`. Use `HEAD` when you only care
  that the server answers and want to avoid transferring a body.
- **Expected status code** — leave blank to accept any 2xx/3xx. Set it when an
  endpoint legitimately returns something else, such as a `401` that proves
  auth is still enforced.
- **Verify TLS certificate** — turn off only for internal services using
  self-signed certificates.

## Website contains keyword

An HTTP check that also requires specific text in the response body. A page can
return `200` while rendering an error, and this is what catches that.

Set **Invert** to fail when the text *is* present — useful for spotting a stack
trace or a maintenance notice that should not be live.

## TCP port

Opens a TCP connection to a host and port and reports whether it was accepted.
Use it for databases, message brokers, SSH — anything that listens but does not
speak HTTP.

## Ping (ICMP)

Sends an ICMP echo request. The lightest check available, but many hosts and
most cloud load balancers drop ICMP, so a failure is not always a real outage.

## DNS record

Resolves a hostname and checks the record type you choose (`A`, `AAAA`,
`CNAME`, `MX`, `TXT`, `NS`). Leave **Expected value** blank to require only
that the record resolves, or set it to catch an unexpected change — a
hijacked `MX`, for instance.

## TLS certificate expiry

Connects to the host and reads its certificate, failing once the certificate
has fewer than **Warn days** left. Certificate expiry is one of the few
outages you can see coming, so this fails *before* anything actually breaks.

Fourteen days is a sensible default; raise it if renewal needs a human.
MD,
            ],
            [
                'slug' => 'alerting-and-incidents',
                'title' => 'Alerting and incidents',
                'category' => 'Basics',
                'sort_order' => 3,
                'excerpt' => 'How a failed check becomes an incident, and who hears about it.',
                'body' => <<<'MD'
## Confirmation thresholds

A single failed check does not mark a monitor down. It has to fail
**confirmation threshold** checks in a row first.

That exists because a dropped packet, a brief DNS hiccup or a load balancer
mid-deploy will all fail one check and recover on the next. Paging someone for
those trains everyone to ignore the alerts.

- `1` — alert on the first failure. Right for a check that runs infrequently.
- `2`–`3` — a good default for anything on a short interval.
- `5` — for noisy targets where you only care about sustained failure.

The trade-off is time to detect: at a 30-second interval with a threshold of
3, a real outage is confirmed after about 90 seconds.

## Incidents

When a monitor is confirmed down, an incident opens. It records the cause, how
many checks failed, and when it started.

The start time is **backdated to the first failure in the streak**, not the
check that happened to cross the threshold. Reported downtime therefore
matches what actually happened rather than under-reporting it by the length of
the confirmation window.

When the monitor recovers the incident is resolved and its duration is fixed.
The **Incidents** page lists everything recorded, open ones first.

## Alert destinations

Two alerts are sent per incident: one when the monitor goes down, one when it
recovers.

**Alerts** covers the simple destinations — email, a custom webhook, Slack and
Discord. Each is fire-and-forget: a message arrives, and nothing happens to it
afterwards.

**Integrations** covers on-call tooling with its own incident lifecycle:

- **PagerDuty** triggers an incident and resolves it on recovery.
- **Opsgenie** opens an alert and closes it on recovery.
- **Microsoft Teams** posts a card; Teams has no lifecycle, so recovery is a
  second card.

PagerDuty and Opsgenie are keyed on the monitor, so a recovery closes the exact
alert the outage opened rather than leaving a page open for someone to clear by
hand.

## Testing

Use **Send test alert** on any channel or integration to send a sample down
alert. Test before you depend on it — a webhook URL that was pasted with a
trailing space fails silently at exactly the wrong moment.

Tests are rate limited to three per minute, and *Check now* to six, because
both cause real outbound traffic.
MD,
            ],
            [
                'slug' => 'status-pages',
                'title' => 'Status pages',
                'category' => 'Basics',
                'sort_order' => 4,
                'excerpt' => 'Publish uptime so customers can check for themselves.',
                'body' => <<<'MD'
A status page is a public URL showing the current state of monitors you choose,
with 90 days of daily uptime history.

## Creating one

Open **Status pages → New status page** and set:

- **Title** and **description** — what visitors see at the top.
- **Slug** — the public address, `/status/your-slug`.
- **Monitors** — tick the ones to publish.
- **Published** — leave off while you get it right; a draft is not reachable.

## What visitors see

An overall banner (all operational, degraded, or pending), then one row per
monitor with its current state and 90 days of daily bars.

Only the monitor's **name** and its uptime are published. The target address,
check configuration, error messages and incident causes are not — so a monitor
called `Payments API` does not reveal the internal hostname behind it.

Name monitors with that in mind: whatever you call one is what the public sees.

## Accessibility

State is never carried by colour alone. Every row pairs its colour with an
icon and a word, and the daily bars vary in height as well as colour, so the
page is readable under any form of colour blindness and in greyscale.
MD,
            ],
            [
                'slug' => 'using-the-api',
                'title' => 'Using the API',
                'category' => 'Automation',
                'sort_order' => 1,
                'excerpt' => 'Scoped API keys and the versioned REST API.',
                'body' => <<<'MD'
Everything you can do to a monitor in the interface, you can do over the API.

## Creating a key

Open **Settings → API keys → New key**. Give it a name you will recognise in
six months, tick only the abilities it needs, and set an expiry.

| Ability | Grants |
| --- | --- |
| `monitors:read` | List and read monitors and their check history |
| `monitors:write` | Create, edit, delete, pause and resume monitors |
| `incidents:read` | List and read incidents |
| `checks:trigger` | Run a check immediately |

The key is shown **once**, at creation. Only a hash is stored, so a lost key
cannot be recovered — issue a new one and revoke the old.

A key can never do more than the account that owns it. Abilities narrow the
account's own permissions; they never widen them.

## Authenticating

Send the key as a bearer token:

```bash
curl https://example.com/api/v1/monitors \
  -H "Authorization: Bearer YOUR_KEY" \
  -H "Accept: application/json"
```

## Endpoints

```
GET    /api/v1/monitors
POST   /api/v1/monitors
GET    /api/v1/monitors/{uuid}
PATCH  /api/v1/monitors/{uuid}
DELETE /api/v1/monitors/{uuid}
PATCH  /api/v1/monitors/{uuid}/state     pause or resume
POST   /api/v1/monitors/{uuid}/check     run a check now
GET    /api/v1/monitors/{uuid}/checks

GET    /api/v1/incidents
GET    /api/v1/incidents/{uuid}
```

Monitors are addressed by UUID. `GET /api/v1/incidents?ongoing=1` returns only
open incidents. List endpoints paginate at 30 by default; `per_page` raises
that to a maximum of 100.

## Creating a monitor

```bash
curl -X POST https://example.com/api/v1/monitors \
  -H "Authorization: Bearer YOUR_KEY" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
        "name": "Payments API",
        "type": "http",
        "url": "https://api.example.com/health",
        "interval_seconds": 60,
        "timeout": 10,
        "confirmation_threshold": 2
      }'
```

## Rate limits

The API allows 120 requests per minute **per key**, so one busy integration
cannot starve another. Every response carries the current budget:

```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 118
```

Triggering a check is limited to six per minute **per account** — that ceiling
is shared across every key you own, because it causes real outbound traffic.

Exceeding a limit returns `429` with a `Retry-After` header and a JSON body.

## Errors

Errors are always JSON, whatever `Accept` header you send.

| Status | Meaning |
| --- | --- |
| `401` | Missing, malformed, revoked or expired key |
| `403` | The key lacks the required ability, or the record is not yours |
| `404` | No such record |
| `422` | Validation failed — see `errors` for the fields |
| `429` | Rate limited — wait `Retry-After` seconds |
MD,
            ],
            [
                'slug' => 'self-hosting',
                'title' => 'Self-hosting',
                'category' => 'Operations',
                'sort_order' => 1,
                'excerpt' => 'What it takes to run Vigil Watch yourself.',
                'body' => <<<'MD'
## What has to be running

Three processes, not one:

1. **The web server** — the interface and API.
2. **A queue worker** — runs checks and delivers alerts. Without it monitors
   are scheduled but never actually execute.
3. **The scheduler** — `php artisan schedule:work`, or a cron entry calling
   `schedule:run` every minute. This dispatches due checks and prunes old
   results.

A missing queue worker is the usual cause of "my monitors never run".

## Requirements

- PHP 8.3 or newer
- MySQL or MariaDB
- Redis, for the queue, cache and sessions

## Configuration

| Variable | Purpose | Default |
| --- | --- | --- |
| `MONITORING_RETENTION_DAYS` | Days of individual check results to keep | `90` |
| `MONITORING_MIN_INTERVAL` | Shortest allowed check interval, in seconds | `30` |
| `MONITORING_QUEUE` | Queue name checks and alerts use | `default` |
| `MONITORING_SEPARATE_QUEUES` | Run checks and alerts on separate queues | `false` |

Incidents are kept indefinitely, so shortening the retention window only
reduces per-check granularity — your outage history survives.

## Scaling

Checks are queued, so throughput is a function of worker count. If alert
delivery starts lagging behind checks, set `MONITORING_SEPARATE_QUEUES=true`
and run a dedicated worker per queue so a slow webhook cannot delay checks.

## Legal pages

The bundled privacy policy and terms are **templates with placeholders**. Edit
them under **Admin → Content** and fill in your own controller details before
putting the service in front of anyone.
MD,
            ],
        ];
    }
}
