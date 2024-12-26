<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

$config
    // Adjusting scanned paths
    ->addPathToScan(__DIR__ . '/examples', isDev: true)
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    ->addPathToScan(__DIR__ . '/vendor', isDev: false)
    ->addPathToExclude(__DIR__ . '/vendor/rector/rector/vendor')
    ->setFileExtensions(['php']) // applies only to directory scanning, not directly listed files

    // Ignoring errors in vendor directory
    ->ignoreErrorsOnPath(__DIR__ . '/vendor', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPath(__DIR__ . '/vendor', [ErrorType::UNKNOWN_FUNCTION])
    ->ignoreErrorsOnPath(__DIR__ . '/vendor', [ErrorType::UNKNOWN_CLASS])
    ->ignoreErrorsOnPath(__DIR__ . '/vendor', [ErrorType::DEV_DEPENDENCY_IN_PROD])

    // do not complain about some modules
    ->ignoreErrorsOnPackage('mimmi20/coding-standard', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('phpstan/extension-installer', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('phpstan/phpstan-phpunit', [ErrorType::UNUSED_DEPENDENCY])

    // Adjust analysis
    ->enableAnalysisOfUnusedDevDependencies() // dev packages are often used only in CI, so this is not enabled by default
    ;

return $config;
