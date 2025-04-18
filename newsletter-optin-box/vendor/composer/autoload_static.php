<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd4095a2753c173a59c96834ab522bdaa
{
    public static $files = array (
        'a4a119a56e50fbb293281d9a48007e0e' => __DIR__ . '/..' . '/symfony/polyfill-php80/bootstrap.php',
        'c19afdcdea21a93bc1162cf72c110965' => __DIR__ . '/..' . '/hizzle/wp/src/ScriptManager.php',
        '574fe3d4b2ffe8c86f902746df6f2ec5' => __DIR__ . '/../..' . '/includes/functions.php',
        'd6774e7ef10b855e2b150eaf0997a2ea' => __DIR__ . '/../..' . '/build/autoload.php',
    );

    public static $prefixLengthsPsr4 = array (
        'T' => 
        array (
            'TijsVerkoyen\\CssToInlineStyles\\' => 31,
        ),
        'S' => 
        array (
            'Symfony\\Polyfill\\Php80\\' => 23,
            'Symfony\\Component\\CssSelector\\' => 30,
        ),
        'H' => 
        array (
            'Hizzle\\Store\\' => 13,
            'Hizzle\\Noptin\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'TijsVerkoyen\\CssToInlineStyles\\' => 
        array (
            0 => __DIR__ . '/..' . '/tijsverkoyen/css-to-inline-styles/src',
        ),
        'Symfony\\Polyfill\\Php80\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-php80',
        ),
        'Symfony\\Component\\CssSelector\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/css-selector',
        ),
        'Hizzle\\Store\\' => 
        array (
            0 => __DIR__ . '/..' . '/hizzle/store/src',
        ),
        'Hizzle\\Noptin\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
            1 => __DIR__ . '/../..' . '/build',
        ),
    );

    public static $classMap = array (
        'Attribute' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/Attribute.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'PhpToken' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/PhpToken.php',
        'Stringable' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/Stringable.php',
        'UnhandledMatchError' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/UnhandledMatchError.php',
        'ValueError' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/ValueError.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd4095a2753c173a59c96834ab522bdaa::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd4095a2753c173a59c96834ab522bdaa::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd4095a2753c173a59c96834ab522bdaa::$classMap;

        }, null, ClassLoader::class);
    }
}
