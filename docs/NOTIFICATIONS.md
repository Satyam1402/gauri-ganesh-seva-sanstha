# Notifications & Communication

How outgoing email and in-app admin notifications work, and how to extend them.

## Overview

| Layer | Where | Responsibility |
|---|---|---|
| Notification classes | `app/Notifications/**` | One class per message. Public ones (donor, applicant, participant, enquirer) extend `BaseNotification`; internal ones extend `AdminNotification`. |
| Dispatch | `App\Services\NotificationService` | The only thing other services call: `send($notifiable, $notification)` and `notifyAdmins($notification, $teamInbox)`. Never throws — delivery problems are logged, the business record is already saved. |
| Recipients | `App\Support\Notifications\AdminRecipients` | Active users who `can()` the category's permission (Gate-evaluated, so Super Admin and future policies are honoured) plus an optional shared mailbox from the module config. |
| Channels | `App\Support\Notifications\ChannelResolver` | `supportedChannels()` of the class ∩ `config('notifications.channels')` ∩ recipient preferences. Public recipients never get the `database` channel. |
| Storage | Laravel's `notifications` table | Admin notifications only. `type` = class name, `data` = `{category, title, message, url}` — no PII beyond a name in the title. |
| Visibility | `NotificationCatalog` + `NotificationRepository` + `NotificationPolicy` | Rows are shown only to their owner **and** only while the owner still holds the category's permission. |
| Email layout | `resources/views/emails/layout.blade.php` | Organisation name, logo, contact details, website, social and legal links come from Site Settings via the `emails.*` view composer (`EmailBranding`). |

## Categories → permissions

`App\Enums\NotificationCategory`: `donation` → manage donations, `volunteer` → manage volunteers, `event` → manage events, `enquiry` → manage contact messages, `comment` → manage blog, `system` → manage settings.

## What is sent when

| Trigger | Public recipient | Admins (in-app + mail unless noted) |
|---|---|---|
| Donation completed (gateway / admin verification) | `DonationConfirmation` (thank-you + receipt, PAN masked) | `NewDonationAlert` |
| Offline donation created (bank transfer / UPI) | — | `NewDonationAlert` ("awaiting verification") |
| Donation confirmed failed | `DonationFailed` | — |
| Volunteer application submitted | `VolunteerApplicationReceived` | `NewVolunteerApplicationAlert` |
| Volunteer status → approved / rejected / under review / on hold | `VolunteerApplicationStatusUpdated` | — |
| Event registration | `EventRegistrationConfirmation` | `NewEventRegistrationAlert` |
| Contact enquiry submitted | `EnquiryAcknowledgement` | `NewEnquiryAlert` |
| Staff reply to an enquiry | `EnquiryReply` | — |
| Blog comment submitted | — | `CommentAwaitingModerationAlert` (in-app only) |
| A queued job exhausts its retries | — | `SystemAlert` (in-app only, sent synchronously) |
| Password reset requested | Laravel `ResetPassword` rendered with the branded layout | — |

Internal notes (`admin_notes`), identity documents, PAN, bank details and secrets are never rendered in any template.

## Queue & failure handling

* Every notification is `ShouldQueue` (`QUEUE_CONNECTION=database`; run `php artisan queue:work`).
* Retries: `NOTIFICATIONS_TRIES` (default 3) with back-off 60 s / 5 min / 15 min. Exhausted jobs go to `failed_jobs` (`queue:failed`, `queue:retry`), are logged, and raise an in-app `SystemAlert` for settings managers.
* `NotificationService` wraps every dispatch in try/catch — a mail or queue outage can never roll back a donation, application or enquiry.
* Payment completion never depends on email: `DonationService::markCompleted()` commits, updates totals, then notifies.

## Adding a channel (SMS / WhatsApp)

1. Add the channel driver (`Notification::extend('sms', …)` in `NotificationServiceProvider`).
2. Flip `sms` to `true` in `config/notifications.php`.
3. Add `routeNotificationForSms()` to the notifiable models (`Donation`, `VolunteerApplication`, `EventRegistration`, `ContactEnquiry`, `User`).
4. Add `'sms'` to `supportedChannels()` of the classes that should use it and implement `toSms()`.

No existing notification, service or template needs to change.

## Adding recipient preferences

Implement `App\Contracts\HasNotificationPreferences` on the model (e.g. `User`) and return the channels the recipient wants per category. `ChannelResolver` applies it automatically.

## Adding a new admin notification

Extend `AdminNotification`, implement `category()`, `title()`, `message()`, optionally `actionUrl()` / `toMail()`, and register the class in `NotificationCatalog::CLASSES` so it is visible in the notification centre.
