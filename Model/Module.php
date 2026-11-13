<?php

namespace Lipscore\RatingsReviews\Model;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Driver\File;

class Module
{
    public const MODULE_NAME = 'Lipscore_RatingsReviews';
    public const PARENT_SOURCE_ID = 'magento2';
    public const PARENT_SOURCE_NAME = 'Magento 2';

    /**
     * @var ComponentRegistrarInterface
     */
    protected $componentRegistrar;

    /**
     * @var File
     */
    protected $fileDriver;

    /**
     * Module constructor.
     *
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param File $fileDriver
     */
    public function __construct(
        ComponentRegistrarInterface $componentRegistrar,
        File $fileDriver
    ) {
        $this->componentRegistrar = $componentRegistrar;
        $this->fileDriver = $fileDriver;
    }

    /**
     * Get the module version from its composer.json.
     *
     * @return string
     */
    public function getModuleVersion()
    {
        $dir = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);
        $composerJson = $dir . DIRECTORY_SEPARATOR . 'composer.json';
        $composerJson = json_decode($this->fileDriver->fileGetContents($composerJson), true);

        if (!isset($composerJson['version'])) {
            return 'No version provided';
        }

        return $composerJson['version'];
    }
}
