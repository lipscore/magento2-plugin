<?php

namespace Lipscore\RatingsReviews\Plugin;

use Magento\Framework\Url;

class UrlPlugin
{
    /**
     * Remove the store scope from the URL params when requested.
     *
     * @param Url $subject
     * @param string|null $routePath
     * @param array|null $routeParams
     * @return array
     */
    public function beforeGetUrl(Url $subject, $routePath = null, $routeParams = null)
    {
        if (isset($routeParams['_ls_remove_scope'])) {
            unset($routeParams['_scope_to_url']);
            unset($routeParams['_ls_remove_scope']);
        }
        return [$routePath, $routeParams];
    }
}
