<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit74e5397d61e5e2f7378f6c1318df9d0e
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInit74e5397d61e5e2f7378f6c1318df9d0e', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit74e5397d61e5e2f7378f6c1318df9d0e', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit74e5397d61e5e2f7378f6c1318df9d0e::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
