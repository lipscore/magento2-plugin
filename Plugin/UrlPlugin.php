<?php

namespace Lipscore\RatingsReviews\Plugin;

class UrlPlugin
{
    public function beforeGetUrl(\Magento\Framework\Url $subject, $routePath = null, $routeParams = null)
    {
        if (isset($routeParams['_ls_remove_scope'])) {
            unset($routeParams['_scope_to_url']);
            unset($routeParams['_ls_remove_scope']);
        }
        return [$routePath, $routeParams];
    }
}
