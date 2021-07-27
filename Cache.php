<?php

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use MatthiasMullie\Scrapbook\Adapters\Flysystem;
use MatthiasMullie\Scrapbook\Psr6\Pool;

class OWMPCache {

  // Hold the class instance.
  private static $instance;

  // Hold the PSR6 cache.
  private Pool $pool;

  private function getFsCache(string $pathToCache) {
    $adapter = new Local($pathToCache, LOCK_EX);
    $filesystem = new Filesystem($adapter);
    // create Scrapbook KeyValueStore object
    $cache = new Flysystem($filesystem);
    return $cache;
  }
  
  // The constructor is private
  // to prevent initiation with outer code.
  private function __construct($pathToCache)
  {
    $fsCache = $this->getFsCache($pathToCache);
    $this->pool = new Pool($fsCache);
  }
 
  // The object is created from within the class itself
  // only if the class has no instance.
  public static function getInstance($pathToCache)
  {
    if (self::$instance == null)
    {
      self::$instance = new OWMPCache($pathToCache);
    }
    return self::$instance->pool;
  }

}
 