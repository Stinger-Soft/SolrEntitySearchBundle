<?php
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Composer\Autoload\ClassLoader;

ini_set('error_reporting', E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

define('TESTS_PATH', __DIR__);
define('TESTS_TEMP_DIR', __DIR__.'/temp');
define('VENDOR_PATH', realpath(__DIR__.'/../vendor'));

/** @var $loader ClassLoader */
$loader = require __DIR__.'/../vendor/autoload.php';


AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
$reader = new AnnotationReader();
$reader = new CachedReader($reader, new ArrayCache());
$_ENV['annotation_reader'] = $reader;