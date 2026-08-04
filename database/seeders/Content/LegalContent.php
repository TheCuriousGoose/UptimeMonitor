<?php

namespace Database\Seeders\Content;

/**
 * Starting text for the privacy policy and terms, drafted for a controller
 * established in the Netherlands: the GDPR plus the UAVG, the cookie rule in
 * art. 11.7a Telecommunicatiewet, and the Dutch Civil Code provisions on
 * algemene voorwaarden and distance contracts.
 *
 * These are a *template*, not legal advice. They describe what this software
 * actually stores, but every deployment has a different controller, KvK
 * number and address, so the bracketed placeholders must be filled in
 * (Admin -> Content) and the result reviewed by a Dutch lawyer before it is
 * relied on.
 */
class LegalContent
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function entries(): array
    {
        return [
            [
                'slug' => 'privacy',
                'title' => 'Privacy policy',
                'excerpt' => 'What personal data this service processes, why, and what rights you have.',
                'body' => self::privacy(),
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms of service',
                'excerpt' => 'The terms on which this service is provided.',
                'body' => self::terms(),
            ],
        ];
    }

    private static function privacy(): string
    {
        return <<<'MD'
> **This is a template.** Replace every `[BRACKETED]` placeholder with your own
> details and have it reviewed by a lawyer before publishing. It is drafted for
> a controller established in the Netherlands and covers what art. 13 GDPR
> requires, but it is not legal advice.

## 1. Who is responsible for your data

The controller (*verwerkingsverantwoordelijke*) responsible for the personal
data described here is:

- **[LEGAL ENTITY NAME B.V. / V.O.F. / EENMANSZAAK]**
- [STREET ADDRESS, POSTCODE, CITY], the Netherlands
- Email: [CONTACT EMAIL]
- KvK number: [KVK NUMBER]
- VAT identification number (btw-id): [NL000000000B00]

[If you have appointed a Functionaris Gegevensbescherming (Data Protection
Officer), give their contact details here and note that they are registered
with the Autoriteit Persoonsgegevens. Under art. 37 GDPR and art. 36 UAVG a
DPO is mandatory where your core activities involve regular and systematic
monitoring of data subjects on a large scale, or large-scale processing of
special-category data.]

## 2. What we process, why, and on what legal basis

### Account data

**What:** your name, email address, a hashed password, and — if you enable
two-factor authentication — your 2FA secret and recovery codes. If you sign in
with Google or GitHub we also store the provider name and your account
identifier at that provider.

**Why:** to create and secure your account and to sign you in.

**Legal basis:** art. 6(1)(b) GDPR — performance of a contract. For the
security measures specifically, art. 6(1)(f) — our legitimate interest in
protecting accounts against unauthorised access.

Under art. 5 UAVG the Netherlands sets the age for valid consent to
information society services at **16**. You must be at least 16 to hold an
account.

### Monitoring configuration and results

**What:** the monitors you create (name, target address, check type, interval,
timeout, thresholds), the notification destinations you configure, and the
result of every check we run — whether it succeeded, how long it took, the
status code, and any error text returned by the target.

**Why:** to run the monitoring service you asked for and to show you its
history.

**Legal basis:** art. 6(1)(b) GDPR — performance of a contract.

Check results can incidentally contain personal data if the systems you
monitor return it in an error message. Point monitors only at endpoints whose
responses you are comfortable storing.

### Technical and security data

**What:** your IP address and browser user-agent are held in your session
record, and requests are rate limited per account.

**Why:** to keep you signed in, and to detect and prevent abuse of the service.

**Legal basis:** art. 6(1)(f) GDPR — our legitimate interest in the security
and availability of the service. [Record the balancing test for this in your
verwerkingsregister under art. 30 GDPR.]

### Email

**What:** transactional email — address verification, password resets, and the
alerts you configure.

**Why:** to operate your account and deliver the alerts you asked for.

**Legal basis:** art. 6(1)(b) GDPR — performance of a contract.

## 3. Whether you have to provide this data

Your name, email address and password are required to hold an account; without
them we cannot provide the service. Everything else — monitors, notification
channels, two-factor authentication — is optional and provided at your choice.

## 4. Who receives your data

- **[HOSTING PROVIDER]**, who hosts the servers this runs on, as a processor
  under art. 28 GDPR. [A verwerkersovereenkomst is in place.]
- **[EMAIL PROVIDER]**, who delivers our transactional email, as a processor.
  [A verwerkersovereenkomst is in place.]
- **Alert destinations you configure.** If you connect Slack, Discord,
  Microsoft Teams, PagerDuty, Opsgenie or a custom webhook, the content of an
  alert — the monitor name, its target address, the error, and the time — is
  sent to that provider when a monitor changes state. You choose these; each
  one is an independent controller for the data it receives, under its own
  privacy policy.
- **Google and GitHub**, if you choose to sign in with them.

We do not sell personal data, and we do not share it for advertising.

## 5. Transfers outside the EU/EEA

Several alert destinations are operated by companies established outside the
EEA, including in the United States. Where an alert is sent to such a
provider, personal data contained in it is transferred outside the EEA.

[State the safeguard you rely on for each transfer — an adequacy decision
under art. 45 (for example the EU–US Data Privacy Framework, where the
recipient is certified), Standard Contractual Clauses under art. 46, or an
art. 49 derogation. List your own processors and where they are established.]

You can avoid these transfers entirely by using only email or a self-hosted
webhook as your alert destination.

## 6. How long we keep it

| Data | Retention |
| --- | --- |
| Account | Until you delete your account |
| Monitors and notification channels | Until you delete them, or the account |
| Individual check results | [90] days, then deleted automatically |
| Incidents | Until the monitor or the account is deleted |
| Sessions | [2] hours after last activity |
| API keys | Until revoked, or until their expiry date |

Deleting your account removes your account record and the monitors, channels,
status pages, incidents and check history attached to it.

[Adjust the check retention row if you have changed `MONITORING_RETENTION_DAYS`,
and add your backup retention period — backups typically outlive a deletion
request, and that has to be disclosed.]

## 7. Your rights

Under the GDPR and the UAVG you have the right to:

- **Access** the personal data we hold about you (art. 15).
- **Rectify** it if it is inaccurate or incomplete (art. 16).
- **Erasure** — have it deleted (art. 17).
- **Restrict** processing in certain circumstances (art. 18).
- **Portability** — receive your data in a structured, commonly used,
  machine-readable format, and have it transmitted to another controller
  (art. 20).
- **Object** to processing based on legitimate interests, on grounds relating
  to your particular situation (art. 21).

Where we rely on consent, you may **withdraw it at any time**, without
affecting the lawfulness of processing carried out before you withdrew it.

To exercise any of these, contact [CONTACT EMAIL]. You can also change your
name and email, and delete your account outright, from your profile settings.
We will respond within one month (art. 12(3)).

## 8. Complaints

You have the right to lodge a complaint with a supervisory authority, in
particular in the Member State of your habitual residence, place of work, or
the place of the alleged infringement (art. 77 GDPR).

Our supervisory authority is the **Autoriteit Persoonsgegevens**, Postbus
93374, 2509 AJ Den Haag —
[autoriteitpersoonsgegevens.nl](https://www.autoriteitpersoonsgegevens.nl).
A list of all EU authorities is published at
[edpb.europa.eu](https://www.edpb.europa.eu/about-edpb/board/members_en).

Complaints about our use of cookies fall to the **Autoriteit Consument &
Markt** ([acm.nl](https://www.acm.nl)), which supervises art. 11.7a
Telecommunicatiewet.

## 9. Automated decision-making

We do not carry out automated decision-making that produces legal effects
concerning you or similarly significantly affects you, within the meaning of
art. 22 GDPR. Monitors evaluate the systems you point them at, not people.

## 10. Cookies

Cookies are governed in the Netherlands by **art. 11.7a Telecommunicatiewet**,
which implements art. 5(3) of the ePrivacy Directive and is enforced by the
ACM.

We set only cookies that are strictly necessary to deliver the service you
requested. Those fall within the exemption in art. 11.7a(3) Tw, so no consent
banner is required:

| Cookie | Purpose | Duration |
| --- | --- | --- |
| Session cookie | Keeps you signed in | [2] hours |
| `XSRF-TOKEN` | Cross-site request forgery protection | Session |
| `appearance` | Remembers your light/dark theme choice | 1 year |
| `sidebar_state` | Remembers whether the sidebar is collapsed | 1 year |

We use no analytics, advertising or third-party tracking cookies.

**If you add any, the exemption no longer applies.** You must then obtain
prior, freely given opt-in consent meeting the GDPR standard. Note the
Autoriteit Persoonsgegevens' position that a cookie wall — refusing access to
visitors who decline — makes consent unfree and therefore invalid, and that
refusing must be as easy and as prominent as accepting.

## 11. Changes

If we change this policy we will update the date shown at the top of this
page. Where a change materially affects how we process your personal data, we
will tell you directly.
MD;
    }

    private static function terms(): string
    {
        return <<<'MD'
> **This is a template.** Replace every `[BRACKETED]` placeholder with your own
> details and have it reviewed by a Dutch lawyer before publishing. It is not
> legal advice.

## 1. Who we are

This service is operated by **[LEGAL ENTITY NAME]**, [STREET ADDRESS,
POSTCODE, CITY], the Netherlands, reachable at [CONTACT EMAIL].

- KvK number: [KVK NUMBER]
- VAT identification number (btw-id): [NL000000000B00]

These details are published here to satisfy the information obligations in
art. 3:15d BW and art. 6:230m BW.

## 2. When these terms apply

These terms are our *algemene voorwaarden*. They apply to every use of the
service and are made available to you before you enter into the agreement, in
a form you can save and reproduce, as art. 6:234 BW requires. You can save
this page or print it to PDF at any time.

If any clause turns out to be void or voidable — including under the *zwarte
lijst* (art. 6:236 BW) or *grijze lijst* (art. 6:237 BW) applicable to
consumers — that clause is replaced by the closest lawful equivalent and the
rest remains in force.

## 3. What the service does

Vigil Watch periodically checks systems you nominate — websites, APIs, TCP
ports, hostnames, DNS records and TLS certificates — records the result, and
notifies the destinations you configure when a check changes state.

## 4. Your account

You must provide accurate registration details and keep your credentials
secure. You are responsible for activity carried out under your account and
under any API key you issue. Tell us promptly at [CONTACT EMAIL] if you
believe your account has been compromised.

You must be at least 16 years old to hold an account (art. 5 UAVG).

## 5. Acceptable use

The check engine issues **real network requests to the targets you configure**.
You may therefore only create monitors for systems you own or have explicit
permission to probe. Pointing this service at third-party infrastructure
without permission may constitute *computervredebreuk* under art. 138ab of the
Dutch Criminal Code.

You must not use the service to:

- Probe, scan or load systems you do not control.
- Generate traffic intended to degrade or overwhelm any system.
- Route alerts to destinations you are not authorised to send to.
- Circumvent the rate limits applied to the interface or the API.

We may suspend an account that breaches this section. Where the breach is not
serious and can be remedied, we will warn you first and give you a reasonable
period to comply.

## 6. Availability

We aim to keep the service running continuously but do not guarantee
uninterrupted availability. Monitoring is a **best-effort signal**: a missed
alert, a false alarm, or a period of unreported downtime is possible — because
of a fault here, a queue backlog, a network partition, or a failure at an
alert destination outside our control.

**Do not rely on this service as the only safeguard for a system where failure
would cause serious harm or loss.**

[If you offer a paid tier with a service level commitment, describe it here,
along with the remedy for missing it.]

## 7. Fees and, for consumers, the right of withdrawal

[This deployment is provided free of charge. / Describe your prices inclusive
of btw, the billing cycle, renewal and how to cancel.]

[**If you sell to consumers**, art. 6:230o BW gives them 14 days to withdraw
from a distance contract without giving reasons. For digital content not
supplied on a tangible medium, that right lapses only if the consumer has
expressly consented to supply beginning within the withdrawal period *and*
acknowledged that they thereby lose the right (art. 6:230p sub g BW). You must
also provide the model withdrawal form. Describe all of that here.]

## 8. Your content

You keep all rights in the data you put into the service — your monitors,
their configuration and their results. You grant us only the permission needed
to store and process that data in order to operate the service for you.

## 9. Intellectual property

The Vigil Watch software is made available under [LICENCE NAME]. These terms
govern your use of **this hosted deployment**; they do not restrict rights
granted to you by that licence over the software itself.

## 10. Warranties and liability

The service is provided "as is" and "as available". To the fullest extent
permitted by law, we exclude implied warranties of merchantability, fitness
for a particular purpose, and non-infringement.

Nothing in these terms excludes or limits our liability for:

- death or personal injury caused by our negligence;
- intent or deliberate recklessness (*opzet of bewuste roekeloosheid*);
- fraud or fraudulent misrepresentation; or
- any other liability that cannot lawfully be excluded or limited.

**If you are a consumer**, you have statutory rights under Dutch law that
these terms do not affect, and nothing here reduces them. A clause on the
*zwarte lijst* of art. 6:236 BW is void against you regardless of what is
written here.

Subject to the above, our total liability arising from the service is limited
to [the amount you paid in the 12 months preceding the claim / the maximum
extent permitted by law]. We are not liable for loss arising from undetected
or unreported downtime, nor for the acts of any alert destination you connect.

## 11. Termination

You may delete your account at any time from your profile settings; doing so
removes your data as described in the privacy policy.

We may suspend or terminate an account that materially breaches these terms.
Except where a breach requires immediate action, we will give you reasonable
notice and an opportunity to put it right.

## 12. Changes to these terms

We may update these terms. Where a change materially affects your rights or
obligations we will give you reasonable notice before it takes effect.
Continuing to use the service after that point means you accept the change; if
you do not, you may delete your account.

## 13. Governing law and disputes

These terms are governed by **Dutch law**. Disputes are submitted to the
competent court in [DISTRICT, e.g. Rechtbank Amsterdam].

**If you are a consumer** this does not deprive you of the protection of
mandatory law, and you retain the right to bring proceedings before the court
of your place of residence. You may also use the European Commission's online
dispute resolution platform at
[ec.europa.eu/consumers/odr](https://ec.europa.eu/consumers/odr/).

## 14. Contact

Questions about these terms: [CONTACT EMAIL].
MD;
    }
}
