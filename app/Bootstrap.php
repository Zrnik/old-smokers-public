<?php declare(strict_types=1);

namespace App;

include __DIR__ . '/../vendor/autoload.php';

use Nette\Application\Application;
use Nette\Bootstrap\Configurator;
use Nette\DI\Container;
use UnexpectedValueException;
use Nette\Utils\Finder;
use Throwable;

class Bootstrap
{
    const DEBUG_DETECT = 0;

    const DEBUG_FORCE_DEVELOPMENT = 10;
    const DEBUG_FORCE_PRODUCTION = 11;

    public static int $debugMode = self::DEBUG_DETECT;
    public static bool $noLocalConfig = false;

    //region createInstance
    private static ?Bootstrap $bs = null;


    public static function instance(): Bootstrap
    {
        if (static::$bs === null)
            static::$bs = new Bootstrap();

        return static::$bs;
    }
    //endregion

    /**
     * @throws Throwable
     */
    public static function run(): void
    {
        static::instance()->getApplication()->run();
    }

    public function getContainer(): Container
    {
        return $this->getConfigurator()->createContainer();
    }

    public function getConfigurator(): Configurator
    {
        $conf = new Configurator();

        $directories = [
            "log" => __DIR__ . '/../log/',
            "temp" => __DIR__ . '/../temp/'
        ];

        foreach ($directories as $dirName => $dirPath)
            if (!file_exists($dirPath))
                mkdir($dirPath);

        if (static::$debugMode !== self::DEBUG_DETECT) // If we have detect mode, nette will decide.
        {
            if (static::$debugMode === self::DEBUG_FORCE_PRODUCTION)
                $conf->setDebugMode(false);

            if (static::$debugMode === self::DEBUG_FORCE_DEVELOPMENT)
                $conf->setDebugMode(true);

            // If its FORCED production mode, still delete
            // nette.configurator and nette.application and latte cache!

            $this->clearCacheDirectory($directories["temp"] . "/cache/latte");
            $this->clearCacheDirectory($directories["temp"] . "/cache/nette.application");
            $this->clearCacheDirectory($directories["temp"] . "/cache/nette.configurator");

        }

        $conf->enableTracy($directories["log"]);

        $conf->setTempDirectory($directories["temp"]);

        $conf->setTimeZone("Europe/Prague");

        $conf->addServices([
            "routing.router" => Router::create()
        ]);

        $conf->addConfig(__DIR__ . '/config/config.neon');

        if ($conf->isDebugMode() || static::$debugMode !== self::DEBUG_DETECT) {
            // If its forced production, we still want a 'local.neon' file!

            //But only if we didn't disable $noLocalConfig (in local phpunit)
            if (!self::$noLocalConfig)
                $conf->addConfig(__DIR__ . '/config/local.neon');
        }

        return $conf;
    }

    public function getApplication(): Application
    {
        /**
         * @var Application $application
         */
        $application = $this->getContainer()->getByType(Application::class);

        return $application;
    }

    private function clearCacheDirectory(string $path): void
    {

        $clearExtensions = [
            "php",
            "lock",
            "meta",
            "s3db"
        ];

        foreach ($clearExtensions as $extension) {
            try {

                foreach (
                    Finder::findFiles('*.' . $extension)->from($path)
                    as $key => $file
                ) {
                    @unlink($key);
                }
            } catch (UnexpectedValueException) {
                //what?
            }
        }
    }
}
