<?php

namespace Lipscore\RatingsReviews\Model;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;

class Module
{
    const MODULE_NAME = 'Lipscore_RatingsReviews';
    const PARENT_SOURCE_ID = 'magento2';
    const PARENT_SOURCE_NAME = 'Magento 2';

    protected $componentRegistrar;

    public function __construct(
        ComponentRegistrarInterface $componentRegistrar
    ) {
        $this->componentRegistrar = $componentRegistrar;
    }

    public function getModuleVersion()
    {
        $dir = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);
        $composerJson = $dir . DIRECTORY_SEPARATOR . 'composer.json';
        $composerJson = json_decode(file_get_contents($composerJson), true);

        if (!isset($composerJson['version'])) {
            return 'No version provided';
        }

        return $composerJson['version'];
    }
}
