<?php

namespace Exordium;

use Composer\IO\IOInterface;
use Composer\Script\Event;

class UniversalConstructor
{

    /**
     * Classes to exclude from autoconstruction
     */
    private static array $excluded;

    /**
     * IOInterface for outputting information
     */
    private static IOInterface $io;

    private static array $composer;

    public static function build(Event $event) : int
    {

        $vendorDir  = $event->getComposer()->getConfig()->get('vendor-dir');
        $configPath = dirname($vendorDir) . '/config';

        self::$io       = $event->getIO();
        self::$composer = self::getComposerOpts($vendorDir);
        self::$excluded = (file_exists("{$configPath}/excludes.php"))
            ? require_once("{$configPath}/excludes.php")
            : [];

        $classnames = array_keys(array_filter(
            require_once $vendorDir . '/composer/autoload_classmap.php',
            ['Exordium\\UniversalConstructor', 'filterClassnameArray'],
            ARRAY_FILTER_USE_BOTH
        ));

        return (self::generateIncludeFile($classnames, $vendorDir))
            ? 1
            : 0;

    }

    private static function getComposerOpts(string $vendorDir) : array
    {

        $composerFile = dirname($vendorDir) . '/composer.json';

        return json_decode(file_get_contents($composerFile), true);

    }

    /**
     * Filters Extremis classes in framework folder from other classes
     *
     * @param  string $path      Vendor directory path
     * @param  string $classname Class name to check
     * @return bool              True if Extremis class in framework folder, false if not
     */
    private static function filterClassnameArray(string $path, string $classname)
    {
        
        $namespace = array_keys(self::$composer['autoload']['psr-4'])[0];
        $classpath = self::$composer['autoload']['psr-4'][$namespace];

        $can_autoconstruct = (
            (strpos($classname, $namespace) !== false) &&
            (strpos($path, $classpath) !== false) &&
            (strpos($classname, 'Abstract') === false) &&
            (strpos($classname, 'Interface') === false)
        );

        if (!$can_autoconstruct) :
            self::$io->write("Class {$classname} excluded by Universal Constructor", true, self::$io::VERBOSE);
            return false;
        endif;

        $is_excluded = in_array($classname, self::$excluded);

        self::$io->write("Class {$classname} included by Universal Constructor", true, self::$io::NORMAL);

        return !$is_excluded;

    }

    /**
     * Generates modules.php file in config directory
     *
     * @param  array  $classnames Classnames to auto construct
     * @param  string $vendorDir  Full path to vendor directory
     * @return int|false          Number of bytes that were written to the file, or false on failure.
     */
    public static function generateIncludeFile(array $classnames, string $vendorDir)
    {

        $output = "<?php\n\nreturn [\n" ;

        foreach ($classnames as $classname) :

            $exploded  = explode('\\', $classname);
            $class     = array_pop($exploded);
            $namespace = strtolower(array_pop($exploded));

            $output .= sprintf(
                "    '%s' => '%s',\n",
                $class,
                $classname
            );

        endforeach;

        $output .= "];";

        return file_put_contents("{$vendorDir}/../config/modules.php", $output);

    }

}
