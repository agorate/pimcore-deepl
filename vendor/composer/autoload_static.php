<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5af5fb69b15a41e658a18fa03648f79c
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'Agorate\\PimcoreDeeplTranslateDocuments\\' => 39,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Agorate\\PimcoreDeeplTranslateDocuments\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit5af5fb69b15a41e658a18fa03648f79c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit5af5fb69b15a41e658a18fa03648f79c::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit5af5fb69b15a41e658a18fa03648f79c::$classMap;

        }, null, ClassLoader::class);
    }
}
