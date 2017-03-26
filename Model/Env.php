<?php

namespace Lipscore\RatingsReviews\Model;

use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;

class Env
{
    const DEV_ENV  = 'development';
    const PROD_ENV = 'production';

    protected $config = [];
    protected $logger;

    public function __construct(
        \Lipscore\RatingsReviews\Model\Logger $logger,
        ModuleDirReader $dirReader
    ){
        $this->logger = $logger;

        try {
            $file = $this->configFilePath($dirReader);
            $this->config = json_decode(file_get_contents($file), true);
        } catch (\Exception $e) {
            $this->logger->log($e);
        }
    }

    public function apiUrl()
    {
        return $this->configValue('api_url');
    }

    public function assetsUrl()
    {
        return $this->configValue('assets_url');
    }

    public static function env()
    {
        return (getenv('LIPSCORE_MAGE_ENV') == self::DEV_ENV) ? self::DEV_ENV : self::PROD_ENV;
    }

    protected function configFilePath(ModuleDirReader $dirReader) {
        $env        = $this->env();
        $moduleDir  = $dirReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Lipscore_RatingsReviews');
        return "$moduleDir/env/$env.json";
    }

    protected function configValue($param)
    {
        if (isset($this->config[$param])) {
            return $this->config[$param];
        } else {
            $this->logger->log('Undefined Lipscore evn parameter ' . $param);
            return '';
        }
    }
}
