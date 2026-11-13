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

### Review request emails: order status detection

`Stores > Configuration > Lipscore > General > Review Request Emails`

A review request is sent to Lipscore when an order reaches the configured **Order status**. The **Order status detection** setting controls how this is detected:

| Option | Behaviour |
| --- | --- |
| On order save (real-time) | Default and previous behaviour. Sent immediately when Magento saves the order with the new status. |
| Scheduled check (cron) | A cron job looks for orders in the configured status. Use it when statuses are changed outside of Magento's order save, e.g. directly in the database by an ERP or third-party extension. Failed requests are retried after 30 minutes, 2, 6 and 24 hours (5 attempts in total). |

In real-time mode the request is sent right after the order save is committed. Failed requests are recorded and can be sent again with the mass action.

Settings for the scheduled check:

- **Check for new orders every**: 5, 10, 15, 30 or 60 minutes (global). Requires a working Magento cron (`bin/magento cron:run`).
- **Check orders updated within the last (days)**: how far back to look for orders in the configured status that have not been sent yet. Default: 7 days, up to 365. Failed requests are only retried within this period, so set at least 2 days. When the Lipscore API cannot be reached 3 times in a row, the run stops and continues on the next run. Older orders can be sent with the mass action described in [Sending review requests manually](#sending-review-requests-manually).

After switching to the scheduled check, orders from this period are sent even if a version before 2.3.0 already sent them. The duplicate settings in your Lipscore account decide whether the customer gets a second request. Orders sent by version 2.3.0 or later are never sent again.

Every review request sent by the extension (in any detection mode, and manually from the order grid) is recorded in the `lipscore_order_reminder` table, together with failed requests. An order is sent automatically at most once: it is not sent again when it returns to the configured status, or after the detection mode is changed. After updating the extension, run `bin/magento setup:upgrade` to create the table.

The review request status is shown in `Sales > Orders`: the **Lipscore Review Request** column (Sent, Failed or Not recorded, with a filter) and the optional **Lipscore Review Request Date** column. The order view page shows the details in the *Lipscore Review Request* section: when and how the request was sent, or the error of a failed request.

### Sending review requests manually

In `Sales > Orders`, select orders and choose **Send Lipscore review request** from the *Actions* menu. Review requests are sent regardless of the order status (also for canceled or closed orders), up to 200 orders at once. When the Lipscore API cannot be reached 3 times in a row, the remaining orders are not processed; run the action again later. Use the **Lipscore Review Request** column filter (*Not recorded* or *Failed*) together with the order status and purchase date filters to find orders that were not sent. Orders already sent by the extension since version 2.3.0 are skipped; orders sent by earlier versions are sent again, subject to the duplicate settings in your Lipscore account. The action requires the *Sales > Operations > Orders > Actions > Send Lipscore Review Request* permission.

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
