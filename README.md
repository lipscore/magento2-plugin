# Lipscore - Supercharged Ratings and Reviews

## Install Lipscore App

###### Install Using Composer

```
composer require lipscore/magento2-plugin
```

###### Or Install Manually

* Download the extension
* Unzip the file
* Create a folder {Magento root}/app/code/Lipscore/RatingsReviews
* Copy the content from the unzip folder
* Flush cache

## Enable Lipscore App

```
php -f bin/magento module:enable --clear-static-content Lipscore_RatingsReviews
php -f bin/magento setup:upgrade
```

If required:
```
php -f bin/magento setup:di:compile
```
