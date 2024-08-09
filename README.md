# Lipscore - Supercharged Ratings and Reviews

Magento 2 plugin by [Lipscore](https://lipscore.com/). Tested with Magento 2 versions up to 2.4.7.

## Installation

### 1. a) Install From Marketplace

[Lipscore Supercharged Reviews](https://commercemarketplace.adobe.com/lipscore-ratingsreviews-magento2.html)

### 1. b) Install Using Composer

```bash
composer require lipscore/ratingsreviews-magento2
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
