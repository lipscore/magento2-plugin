# Lipscore - Supercharged Ratings and Reviews

Magento 2 plugin by [Lipscore](https://lipscore.com/). Tested with Magento 2 versions up to 2.4.7.

## Requirements

- Magento Open Source / Adobe Commerce 2.4.x, up to and including 2.4.9
- PHP 7.4 or 8.1 – 8.5 (as supported by your Magento version)

## Installation

### 1. a) Install From Marketplace

[Lipscore Supercharged Reviews](https://commercemarketplace.adobe.com/lipscore-ratingsreviews-magento2.html)

### 1. b) Install Using Composer

```bash
composer require lipscore/ratingsreviews-magento2:2.3.0
```

### 1. c) Install Manually

- Download the extension
- Unzip the file
- Create a folder `<Magento-root>/app/code/Lipscore/RatingsReviews`
- Copy the content from the unzipped folder
- Flush cache

### 2. Enable Lipscore App

```bash
bin/magento module:enable --clear-static-content Lipscore_RatingsReviews
bin/magento setup:upgrade
```

If required:

```bash
bin/magento setup:di:compile
```

### 3. Configure Lipscore App

[Lipscore Documentation](https://lipscore.com/ikb/magento-guide/)

### Invitations

`Stores > Configuration > Lipscore > General > Invitations`

When an order reaches the configured **Order status**, the extension schedules an invitation in Lipscore (`POST /invitations`). Lipscore sends the invitation email to the customer later, according to your Lipscore settings. The **Order status detection** setting controls how the status change is detected:

| Option | Behaviour |
| --- | --- |
| On order status save | Default and previous behaviour. The invitation is scheduled right after Magento commits the order save with the new status, one API request per order. |
| Scheduled check (cron) | An hourly cron job looks for orders in the configured status and schedules their invitations in batches of up to 20 per API request. Use it when statuses are changed outside of Magento's order save, e.g. directly in the database by an ERP or third-party extension. |

The hourly check (`lipscore_schedule_invitations`, requires a working Magento cron) also retries invitations that failed because of a temporary error, in both modes: after 1, 3, 6 and 24 hours (5 attempts in total). It processes up to 500 orders per run.

**Check orders updated within the last (days)** (scheduled check only): how far back to look for orders in the configured status without a scheduled invitation. 1, 7 (default), 14 or 30 days. Failed invitations are only retried within this period, so set at least 7 days. Older orders can be scheduled with the bulk action described in [Scheduling invitations manually](#scheduling-invitations-manually).

#### How API responses are handled

| Lipscore response | Status | Retried |
| --- | --- | --- |
| 201 Created | Scheduled | – |
| 409 Conflict (invitation exists, or the customer is within a repeat-block period) | Already scheduled, with the Lipscore message | No |
| 400, 422 (invalid invitation data, products blocked) | Rejected, with the Lipscore message | No; correct the data and use the bulk action |
| Timeout, connection error, 5xx, 503, 429, 403 "Feature is not available at the moment" | Failed | Yes, by the hourly check |
| 401, other 403 (credentials, IP, blocked or cancelled site, master account, invitation limit) | Failed | The store is paused for 24 hours or until its API key, secret or URL changes |

Within a batch, Lipscore creates the valid invitations and returns only those. Orders missing from the response are sent again one by one to record their individual result. A timeout, server or account error stops the store for the current run; its remaining orders are processed in the next run. If recording a result fails after Lipscore created the invitation, the order is sent again later and Lipscore answers 409, so no duplicate invitation is created.

Lipscore detects duplicate and repeat-purchase invitations according to the settings in *Invitation setup* in the Lipscore dashboard.

#### Invitation log

Every invitation scheduled by the extension (in any mode, and from the order grid) is recorded in the `lipscore_invitation` table, together with failed ones. An order is scheduled automatically at most once: not again when it returns to the configured status, or after the detection mode is changed. After updating the extension, run `bin/magento setup:upgrade` to create the table.

The status is shown in `Sales > Orders`: the **Lipscore Invitation** column (Scheduled, Already scheduled, Failed, Rejected or Not recorded, with a filter) and the optional **Lipscore Invitation Date** column. The order view page shows the details in the *Lipscore Invitation* section, including the Lipscore message.

After switching to the scheduled check, orders from the look-back period are scheduled even if a version before 2.3.0 already sent them; Lipscore detects the duplicates.

### Scheduling invitations manually

In `Sales > Orders`, select orders and choose **Schedule Lipscore invitations** from the *Actions* menu. Invitations are scheduled regardless of the order status (also for canceled or closed orders), up to 200 orders at once, in batches of 20. Orders whose invitation is already scheduled (or reported by Lipscore as already scheduled) are skipped; failed and rejected ones are sent again. Use the **Lipscore Invitation** column filter (*Not recorded*, *Failed* or *Rejected*) together with the order status and purchase date filters to find orders without an invitation. The action requires the *Sales > Operations > Orders > Actions > Schedule Lipscore Invitations* permission.

<!--
## Local Development

1. Make sure Docker is installed.
2. Set up sample Magento store locally using [this repository](https://github.com/markshust/docker-magento?tab=readme-ov-file#automated-setup-new-project) (automated setup). You will be requested to provide acess keys - you can set up an account [here](https://experienceleague.adobe.com/en/docs/commerce-operations/installation-guide/prerequisites/authentication-keys) and generate them.
3. Verify that your store is running on https://magento.test/ or different host, and has sample products.
4. Run `bin/create-user` command to create an admin account.
5. Disable 2FA before logging to the admin account:

```bash
bin/magento module:disable Magento_AdminAdobeImsTwoFactorAuth
bin/magento module:disable Magento_TwoFactorAuth
bin/magento setup:upgrade
```

7. Go to /admin route and log in to admin dashboard.
8. Your local store is set up. You can now follow instructions to install the Lipscore plugin.
9. To test reviews, you can go to "Orders", choose specific order and click "Reorder". This will quickly create an order in Pending state and create invitation on your Lipscore account. You can immediately send the invitation, rate the product and see the results on your store.
-->
