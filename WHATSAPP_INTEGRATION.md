# WhatsApp Chat Integration

Full guide for setting up Meta WhatsApp Business Cloud API and how Ceysaid (TravelSync) implements inbound/outbound chat in code.

---

## Overview

| Area | Details |
|------|---------|
| API | Meta WhatsApp Business **Cloud API** (Graph API, default `v21.0`) |
| Pattern | Single WhatsApp Business Account (WABA) + one phone number |
| Inbound | Meta webhook → Laravel API → queue job → DB + Filament inbox |
| Outbound | Filament reply → queue job → Graph API `/{phone-number-id}/messages` |
| Auth to Meta | Permanent **System User** access token (`WHATSAPP_ACCESS_TOKEN`) |
| Webhook auth | Verify token (GET) + `X-Hub-Signature-256` HMAC (POST) |

**Inbound can work while outbound fails** if the access token is expired: webhooks are pushed by Meta; sending requires a valid token.

---

## Part 1 — Meta setup

### 1. Prerequisites

1. **Meta Business Manager** account  
2. **WhatsApp Business Account (WABA)** under that business  
3. A **phone number** registered on WhatsApp Cloud API (not the consumer WhatsApp app for that number)  
4. A **Meta Developer App** with the **WhatsApp** product added  
5. A public **HTTPS** URL for webhooks (production dashboard, e.g. `https://your-domain.com`)

### 2. Create / configure the Developer App

1. Open [Meta for Developers](https://developers.facebook.com/apps/) → **Create App** (type that supports Business / WhatsApp).  
2. Add product **WhatsApp**.  
3. Under **WhatsApp → API Setup**, note:
   - **Phone number ID** → `WHATSAPP_PHONE_NUMBER_ID`
   - **WhatsApp Business Account ID** → optional reference (`WHATSAPP_BUSINESS_ACCOUNT_ID` if you store it)
   - Temporary test token (short-lived — **do not use in production**)
4. Under **App Settings → Basic**:
   - **App ID**
   - **App Secret** → `WHATSAPP_APP_SECRET` (required for webhook signature verification)

### 3. Permanent access token (System User)

Temporary tokens from API Setup expire (typically ~60 days or less). Production must use a **permanent System User** token.

1. [Business Settings](https://business.facebook.com/settings) → **Users → System users**  
2. Prefer an Admin system user dedicated to CRM (e.g. **Ceysaid CRM API**)  
3. **Add assets**:
   - The WhatsApp account / WABA  
   - The Developer App  
4. **Generate token**:
   - Select the WhatsApp-enabled app  
   - Permissions (minimum):
     - `whatsapp_business_messaging`
     - `whatsapp_business_management` (recommended for media / account ops)
5. Copy the token once → store as `WHATSAPP_ACCESS_TOKEN` (server `.env` only; never commit)

#### Verify token (expiry + which app/user)

**UI:** [Access Token Debugger](https://developers.facebook.com/tools/debug/accesstoken/)

- Valid token: shows **App ID**, **User ID** (system user), **Expires** (`Never` for permanent), **Scopes**, **Is Valid**  
- **Expired token**: debugger shows only the expiry error — **no app/user metadata**

**API** (works even for some expired tokens when using App access token):

```bash
curl -s "https://graph.facebook.com/v21.0/debug_token\
?input_token=TOKEN_TO_CHECK\
&access_token=APP_ID|APP_SECRET" | jq
```

Check:

| Field | Meaning |
|-------|---------|
| `data.is_valid` | Token usable for API calls |
| `data.expires_at` | Unix expiry; `0` = never |
| `data.app_id` | Which Developer App |
| `data.user_id` | Which System User |
| `data.scopes` | Granted permissions |

Quick live check:

```bash
curl -s "https://graph.facebook.com/v21.0/me?access_token=WHATSAPP_ACCESS_TOKEN"
curl -s "https://graph.facebook.com/v21.0/PHONE_NUMBER_ID?fields=display_phone_number,verified_name&access_token=WHATSAPP_ACCESS_TOKEN"
```

If you see `OAuthException` **code 190** (“token has expired”), regenerate a System User token and update `.env`, then clear config cache.

### 4. Webhook configuration

In the Developer App → **WhatsApp → Configuration** (or Webhooks):

| Setting | Value |
|---------|--------|
| Callback URL | `https://YOUR_PUBLIC_HOST/api/webhooks/whatsapp` |
| Verify token | Same random string as `WHATSAPP_VERIFY_TOKEN` in `.env` |
| Webhook fields | Subscribe at least: **`messages`** (covers inbound messages + delivery status updates) |

Meta will:

1. **GET** the callback with `hub.mode`, `hub.verify_token`, `hub.challenge` — app must echo `hub.challenge` if the verify token matches.  
2. **POST** JSON payloads with header `X-Hub-Signature-256: sha256=<hmac>` signed with the **App Secret**.

### 5. Click-to-WhatsApp (CTWA) ads (optional)

If ads open a WhatsApp thread:

1. Create ads that use the same WABA / phone number  
2. Inbound webhooks include a `referral` object (`source_id`, `ctwa_clid`, headline, etc.)  
3. Ceysaid stores these on the conversation (and copies them onto a lead when created from chat)

### 6. Messaging window (Meta policy)

Within **24 hours** of the customer’s last message, you may send free-form session messages (text/media). Outside that window, Meta requires an **approved message template**.  

**This codebase does not yet implement template sends or a 24-hour window check.** Replies always use the session message API; Meta may reject them outside the window.

---

## Part 2 — Application configuration

### Environment variables

Defined in `.env` / `.env.example` and loaded via `config/whatsapp.php`:

| Variable | Purpose |
|----------|---------|
| `WHATSAPP_ACCESS_TOKEN` | System User token for Graph API send/media |
| `WHATSAPP_PHONE_NUMBER_ID` | Cloud API phone number ID (not the E.164 digits alone) |
| `WHATSAPP_VERIFY_TOKEN` | Shared secret for webhook GET verification |
| `WHATSAPP_APP_SECRET` | App secret for POST signature HMAC |
| `WHATSAPP_API_VERSION` | Graph version (default `v21.0`) |
| `WHATSAPP_WEBHOOK_PATH` | Documented path (routes use `/api/webhooks/whatsapp`) |
| `WHATSAPP_MEDIA_DISK` | Filesystem disk name (`whatsapp-media`) |
| `WHATSAPP_MEDIA_DRIVER` | `local` or `s3` |
| `WHATSAPP_MEDIA_S3_ROOT` | S3 prefix when using S3 |
| `WHATSAPP_MAX_IMAGE_SIZE_KB` | Outbound image limit (default 5120) |
| `WHATSAPP_MAX_DOCUMENT_SIZE_KB` | Outbound doc limit (default 16384) |
| `WHATSAPP_LOG_INBOUND_MESSAGES` | Log full webhook payloads (`true`/`false`) |
| `LIVEWIRE_TEMP_UPLOAD_DISK` | Should be `local` for Filament uploads |
| `QUEUE_CONNECTION` | Must run a worker (`database` / `redis` / etc.) |

Also ensure `APP_URL` is the public HTTPS origin used in Meta’s callback URL.

### Config file

`config/whatsapp.php` — tokens, Graph base URL (`https://graph.facebook.com`), allowed outbound MIME types/extensions, media size limits.

### Media disk

`config/filesystems.php` disk `whatsapp-media`:

- Local: `storage/app/whatsapp-media`  
- S3: uses AWS credentials + `WHATSAPP_MEDIA_S3_ROOT`  
- Visibility: private (served through authenticated controller)

### Queue worker (required)

Outbound sends, webhook processing, and media downloads are **queued**:

```bash
php artisan queue:work
```

Use Supervisor/systemd in production so the worker stays up. Without a worker, messages stay `pending` and webhooks are not fully processed.

### Deploy checklist

1. Set all `WHATSAPP_*` env vars on the server  
2. `php artisan config:clear` (or `config:cache` after setting env)  
3. Run migrations  
4. Ensure queue worker is running  
5. Register webhook in Meta and confirm GET verification succeeds  
6. Send a test WhatsApp to the business number and confirm a row in Filament **WhatsApp Inbox**  
7. Assign chat → reply → confirm customer receives message  
8. Debug the access token and confirm **Expires: Never**

---

## Part 3 — Code architecture

### High-level flow

```
┌─────────────┐     HTTPS POST      ┌──────────────────────────┐
│ Meta Cloud  │ ──────────────────► │ WhatsAppWebhookController │
│ API / Ads   │     + signature     │  + VerifyWhatsAppSignature│
└─────────────┘                     └────────────┬─────────────┘
                                                 │ record WebhookEvent
                                                 ▼
                                    ProcessWhatsAppWebhookJob
                                                 │
                                                 ▼
                                    WhatsAppWebhookHandler
                                      ├─ messages → Contact / Conversation / Message
                                      │              └─ DownloadWhatsAppMediaJob
                                      └─ statuses → Message + MessageStatus

┌─────────────┐   sendReply()   ┌─────────────────────┐
│ Filament UI │ ──────────────► │ WhatsAppMessage     │
│ Conversation│                 │ status=pending      │
└─────────────┘                 └──────────┬──────────┘
                                           │
                                           ▼
                                 SendWhatsAppMessageJob
                                           │
                                           ▼
                                 WhatsAppApiService ──► Graph API
```

### Key files

| Path | Role |
|------|------|
| `routes/api.php` | Webhook GET/POST under `/api/webhooks/whatsapp` |
| `routes/web.php` | Authenticated media stream `whatsapp.media` |
| `app/Http/Controllers/WhatsAppWebhookController.php` | Verify + receive |
| `app/Http/Middleware/VerifyWhatsAppSignature.php` | HMAC validation |
| `app/Http/Controllers/WhatsAppMediaController.php` | Private media download for staff |
| `app/Services/WhatsAppWebhookHandler.php` | Parse inbound + statuses |
| `app/Services/WhatsAppApiService.php` | Graph send/upload/download |
| `app/Services/WhatsAppLeadService.php` | Manual lead from conversation |
| `app/Services/WhatsAppChatFolderService.php` | Per-user chat folders |
| `app/Jobs/ProcessWhatsAppWebhookJob.php` | Async webhook handling |
| `app/Jobs/SendWhatsAppMessageJob.php` | Async outbound send |
| `app/Jobs/DownloadWhatsAppMediaJob.php` | Async inbound media fetch |
| `app/Exceptions/WhatsAppApiException.php` | API errors (+ Meta body) |
| `app/Support/WhatsAppMediaStorage.php` | Store/read media on disk |
| `app/Support/WhatsAppLogContext.php` | Flatten nested log context |
| `app/Filament/Resources/WhatsAppInboxResource.php` | Unassigned inbox |
| `app/Filament/Resources/MyWhatsAppChatResource.php` | Assigned chats |
| `app/Filament/Resources/MyWhatsAppChatResource/Pages/ConversationPage.php` | Reply UI |
| `app/Console/Commands/BackfillWhatsAppReferralsCommand.php` | `whatsapp:backfill-referrals` |

### Routes

```text
GET  /api/webhooks/whatsapp   → verify (hub challenge)
POST /api/webhooks/whatsapp   → receive (signature middleware)
GET  /admin/whatsapp-media/{message}  → stream attachment (auth)
```

### Webhook verification (GET)

`WhatsAppWebhookController::verify`:

- Requires `hub_mode=subscribe` and `hub_verify_token` === `config('whatsapp.verify_token')`  
- Returns plain-text `hub_challenge` with HTTP 200  
- Otherwise 403

### Webhook receive (POST)

1. Optional full payload log (`WHATSAPP_LOG_INBOUND_MESSAGES`)  
2. `WhatsAppWebhookHandler::recordWebhookEvent()` — idempotent store in `webhook_events`  
3. `ProcessWhatsAppWebhookJob::dispatch($event->id)`  
4. Immediate `200 EVENT_RECEIVED` (Meta retries if non-2xx)

**Signature middleware:** HMAC-SHA256 of raw body with `WHATSAPP_APP_SECRET` must match `X-Hub-Signature-256`. If app secret is empty, verification is **skipped** (logged as warning) — always set the secret in production.

### Inbound processing

`WhatsAppWebhookHandler` expects `object = whatsapp_business_account`, then `entry[].changes[]`:

| Change | Behaviour |
|--------|-----------|
| `messages` | Upsert contact (`wa_id` / phone / profile name); get-or-create conversation (one per contact); create inbound `WhatsAppMessage`; apply CTWA `referral` to conversation; increment unread; queue media download if needed |
| `statuses` | Append `WhatsAppMessageStatus`; update message status (`sent` / `delivered` / `read` / `failed`, etc.) |

Supported inbound types (parsed): text, image, document, audio, video, sticker, location; others stored as unknown/generic.

**Leads are not auto-created** on inbound message. Staff create a lead from the conversation UI when needed.

### Outbound processing

1. `ConversationPage::sendReply()` (admin if chat assigned; sales if assignee):  
   - Text (max 4096) and/or allowed attachment  
   - Creates `WhatsAppMessage` with `direction=outbound`, `status=pending`, temporary `wamid` (`local-{uuid}`)  
   - Dispatches `SendWhatsAppMessageJob`  
2. Job:  
   - Text → `WhatsAppApiService::sendTextMessage`  
   - Media → read from disk → `uploadMedia` → `sendMediaMessage`  
   - On success: real Meta `wamid`, `status=sent`, `sent_at`  
   - On failure: `status=failed`, error logged on `whatsapp` channel (includes Meta error when available)

Phone numbers are normalized to digits only before send.

### Filament UX

| Resource | Who | Purpose |
|----------|-----|---------|
| **WhatsApp Inbox** | Admin / Sales | Unassigned conversations; **Assign to me** |
| **My WhatsApp Chats** | Admin (all assigned) / Sales (own) | Reply, folders, create lead, open ad |

- Conversation view polls for new messages  
- Folders are per-user (`WhatsAppChatFolder`)  
- Create lead copies contact + CTWA fields via `WhatsAppLeadService`

### Data model (summary)

| Table | Purpose |
|-------|---------|
| `whatsapp_contacts` | `wa_id`, phone, profile name |
| `whatsapp_conversations` | Contact link, assignee, folder, CTWA fields, unread, last message |
| `whatsapp_messages` | Inbound/outbound body, media, status, `wamid`, referral JSON |
| `whatsapp_message_statuses` | Status history from webhooks |
| `whatsapp_chat_folders` | User-defined folders |
| `webhook_events` | Idempotent raw webhook storage |

Migrations live under `database/migrations/*whatsapp*`.

### Logging

Channel `whatsapp` → daily files `storage/logs/whatsapp-YYYY-MM-DD.log` (14-day retention). Used for verification, payloads, API failures, send failures.

---

## Part 4 — Operations runbook

### Symptom: messages arrive but replies fail

1. Check `storage/logs/whatsapp-*.log` and `laravel.log` for `Failed to send WhatsApp message`  
2. Debug `WHATSAPP_ACCESS_TOKEN` (expired → OAuth **190**)  
3. Confirm queue worker is running and `failed_jobs` is not filling  
4. Confirm `WHATSAPP_PHONE_NUMBER_ID` matches Meta API Setup  
5. If Meta returns a messaging-window / template error, customer must message first or you need template support (not in app yet)

### Rotate / renew token

1. Business Settings → System users → **Ceysaid CRM API** (or your CRM user)  
2. Generate new permanent token with WhatsApp permissions  
3. Update production (and local) `WHATSAPP_ACCESS_TOKEN`  
4. `php artisan config:clear`  
5. Re-test send; confirm debugger shows **Never** expires  

### Test webhook locally

Use a tunnel (ngrok, Cloudflare Tunnel, etc.):

```text
Callback URL: https://<tunnel>/api/webhooks/whatsapp
```

Point Meta webhook at the tunnel; keep `queue:work` running locally.

### Useful artisan

```bash
php artisan queue:work
php artisan queue:failed
php artisan whatsapp:backfill-referrals   # backfill CTWA referral data when needed
```

---

## Part 5 — Security notes

- Never commit access tokens or app secrets  
- Always set `WHATSAPP_APP_SECRET` so signature verification is enforced  
- Media is private; only authenticated staff with access to the conversation can stream via `whatsapp.media`  
- Prefer permanent System User tokens over user tokens that expire  

---

## Related docs

- `.env.example` — WhatsApp env stubs  
- `ANALYTICS_SETUP.md` — lead `platform` includes `whatsapp`  
- Meta: [Cloud API overview](https://developers.facebook.com/docs/whatsapp/cloud-api)  
- Meta: [Webhooks](https://developers.facebook.com/docs/whatsapp/cloud-api/guides/set-up-webhooks)  
- Meta: [Access Token Debugger](https://developers.facebook.com/tools/debug/accesstoken/)

---

## Known limitations

- No template message sending UI/API  
- No explicit 24-hour customer-care window enforcement in app code  
- One conversation per WhatsApp contact  
- Inbox assignment is self-claim (“Assign to me”), not manager-assign workflow beyond Filament permissions  
- Signature verification is skipped if `WHATSAPP_APP_SECRET` is empty  
