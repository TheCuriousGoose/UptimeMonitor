<?php

namespace Database\Seeders\Content;

/**
 * Starting blog posts. Each one explains a decision the codebase actually
 * makes, so they stay honest rather than reading as filler.
 */
class BlogContent
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function entries(): array
    {
        return [
            [
                'slug' => 'why-we-backdate-incidents',
                'title' => 'Why we backdate incidents',
                'publishedDaysAgo' => 21,
                'excerpt' => 'Reported downtime should match what actually happened, not when we noticed.',
                'body' => <<<'MD'
Most uptime tools start an incident at the moment they decide a monitor is
down. That sounds obvious, and it quietly under-reports every outage you have.

## The gap

A monitor with a 30-second interval and a confirmation threshold of 3 will not
be marked down until three checks in a row have failed. That is 90 seconds
after the first failure.

If the incident starts when the *third* check fails, the first 90 seconds of
every outage vanishes. Run a few dozen incidents through that and your uptime
figure is measurably better than reality.

## What we do instead

When an incident opens, its start time is backdated to the **first failure in
the streak** — not the check that crossed the threshold:

```
14:00:00  check fails   <- incident starts here
14:00:30  check fails
14:01:00  check fails   <- monitor confirmed down here
```

The confirmation threshold still does its job: nobody is paged at 14:00:00,
because a single failure is usually noise. But once the outage is real, it is
recorded from the moment it began.

## Why the threshold still matters

The obvious alternative is to drop confirmation entirely and alert on the
first failure. That gets you accurate start times and an alert channel nobody
reads, because a dropped packet or a load balancer mid-deploy will fail one
check and recover on the next.

Confirmation decides **when to wake someone**. Backdating decides **what to
record**. They are different questions and they deserve different answers.
MD,
            ],
            [
                'slug' => 'a-deadlock-that-ate-checks',
                'title' => 'A deadlock that ate checks',
                'publishedDaysAgo' => 9,
                'excerpt' => 'Two writers, two lock orders, and a job that only tried once.',
                'body' => <<<'MD'
Some checks were silently going missing under load. The database told us why:

```
SQLSTATE[40001]: Serialization failure: 1213
Deadlock found when trying to get lock
```

## Two writers, opposite orders

Two things write to the `monitors` table.

The **dispatcher** runs every 30 seconds, finds monitors that are due, and
pushes their next check time forward so a backed-up queue cannot dispatch the
same monitor twice.

The **workers** run the checks. When one finishes it opens a transaction and
writes the check row, the monitor's new status, and any incident change.

The worker's transaction locked `monitor_checks` first and `monitors` second.
The dispatcher locked `monitors` directly. Two transactions, two orders, and
with several workers running in parallel, eventually a cycle.

## Why the checks disappeared

A deadlock is survivable — the database picks a victim, rolls it back, and
that transaction can be retried.

Ours was not being retried. The check job was configured with `tries = 1`, so
the rolled-back transaction meant the job died and the result was never
recorded. The check had genuinely run, the target had genuinely answered, and
the answer was thrown away.

## The fix

Three parts, in order of how much they mattered:

**Take the locks in the same order.** The worker now locks the monitor row
first, before touching `monitor_checks` or `incidents`. Every writer that
reaches these tables acquires locks in the same sequence, so they queue behind
each other instead of deadlocking.

**Retry the transaction, not the check.** The persist step now runs with three
attempts. Retrying the whole job would re-probe the target and record a second
result; retrying just the write recovers without touching the network.

**Claim the batch in one statement.** The dispatcher now updates the whole
batch with a single `UPDATE ... WHERE id IN (...)` rather than one save per
row, which collapses hundreds of interleaved short locks into one.

## The part that nearly bit us

Retrying a transaction is only safe if it is idempotent, and ours was not.

The old code incremented the failure streak on the in-memory model. A retried
attempt would have incremented a counter the rolled-back attempt had already
bumped — turning a deadlock into silently wrong data, which is worse than a
lost check.

Taking the row lock re-reads committed state, so the arithmetic starts from
the database on every attempt. There are now tests that fail if that read goes
away.
MD,
            ],
            [
                'slug' => 'colour-is-not-a-status',
                'title' => 'Colour is not a status',
                'publishedDaysAgo' => 34,
                'excerpt' => 'Red and green are the same colour to a lot of people.',
                'body' => <<<'MD'
Around one in twelve men has some form of red-green colour blindness. A status
dashboard that distinguishes up from down using only red and green is
unreadable to them — which is a strange thing to ship when the entire purpose
of the page is to communicate state at a glance.

## The rule we follow

Colour never carries meaning on its own. Every status indicator pairs it with
at least one other channel:

- **Status labels** carry an icon and a word: a tick and `UP`, a cross and
  `DOWN`. The colour reinforces; it does not inform.
- **Uptime timeline bars** vary in height as well as colour. A failing bar is
  full height, a healthy one is scaled by response time, and a bar with no
  data is a short stub. The shape of the strip reads correctly in greyscale.
- **Incident durations** are text, and an open incident says `Ongoing` rather
  than relying on being the red one.

## Why height, not just an icon

The timeline draws one bar per bucket, a few pixels wide. There is no room for
an icon inside a 4-pixel bar.

Height works at that scale because the eye reads a silhouette before it reads
colour. Scan the strip and the failures stand out as spikes whether or not you
can tell red from green — and whether or not you are looking at a screenshot
someone pasted into a chat in greyscale.

## The status page too

Public status pages follow the same rule. Every monitor row has an icon and a
word alongside its colour, and the 90-day history uses the same height
encoding. That page is the one your customers see, and you have no idea how
any of them perceive colour.
MD,
            ],
        ];
    }
}
