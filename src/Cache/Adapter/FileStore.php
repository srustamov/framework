<?php

namespace TT\Cache\Adapter;

use BadMethodCallException;
use Closure;
use ReflectionException;
use RuntimeException;
use TT\File;

class FileStore implements CacheStoreInterface
{
    private $path;

    private $fullPath;

    private $put = false;

    private $key;

    private $expires;

    /**@var $file File */
    private $file;


    /**
     * FileStore constructor.
     * @param File $file
     * @param array $config
     */
    public function __construct(File $file, array $config)
    {
        if (!array_key_exists('path', $config)) {
            throw new RuntimeException(sprintf(
                'Cache adapter [%s] required config [path] parameter',
                static::class
            ));
        }
        $this->path = $config['path'];

        $this->file = $file;

        $this->gc();
    }


    /**
     * @param String $key
     * @param $value
     * @param null $expires
     * @param bool $forever
     * @return $this
     */
    public function put(String $key, $value, $expires = null, $forever = false)
    {
        $paths = $this->getpaths($key);

        $this->fullPath = $paths->fullPath;

        if (!$this->has($key)) {
            $this->createDir($paths);
        }

        if ($value instanceof Closure) {
            $value = $value($this);
        }


        if ($expires === null) {
            if ($this->expires === null && !$forever) {
                $this->put = true;

                $this->key = $key;

                $this->file->write($paths->fullPath, serialize($value));
            } else {
                $this->file->write($paths->fullPath, serialize($value));

                touch($paths->fullPath, ($forever ? -2 : (time() + $this->expires)));

                $this->expires = null;
            }
        } else {
            $this->file->write($paths->fullPath, serialize($value));

            touch($paths->fullPath, time() + $expires);

            $this->expires = null;
        }

        return $this;
    }


    /**
     * @param String $key
     * @param $value
     * @return $this
     */
    public function forever(String $key, $value)
    {
        $this->put($key, $value, null, true);

        return $this;
    }


    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        if ($key instanceof Closure) {
            $key = $key($this);
        }

        return $this->existsExpires($this->getpaths($key));
    }


    /**
     * @param $key
     * @return bool|mixed
     */
    public function get($key)
    {
        if ($key instanceof Closure) {
            $key = $key($this);
        }

        $paths = $this->getpaths($key);

        if ($this->existsExpires($paths)) {
            return unserialize($this->file->get($paths->fullPath), ['allowed_classes' => null]);
        }
        return false;
    }


    /**
     * @param $key
     */
    public function forget($key)
    {
        if ($key instanceof Closure) {
            $key = $key($this);
        }

        $paths = $this->getpaths($key);

        if (file_exists($paths->fullPath)) {
            unlink($paths->fullPath);
        }

        if ($this->file->dirIsEmpty($this->path . '/' . $paths->path1 . '/' . $paths->path2)) {
            $this->file->rmdir_r($this->path . '/' . $paths->path1 . '/' . $paths->path2);
            if ($this->file->dirIsEmpty($this->path . '/' . $paths->path1)) {
                $this->file->rmdir_r($this->path . '/' . $paths->path1);
            }
        }
    }


    /**
     * @param $paths
     * @return mixed
     */
    private function createDir($paths)
    {
        if (!file_exists($paths->fullPath)) {
            if (
                !file_exists($this->path . '/' . $paths->path1 . '/') &&
                !mkdir($concurrentDirectory = $this->path . '/' . $paths->path1 . '/', 0755, false) && !is_dir($concurrentDirectory)
            ) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }

            if (
                !mkdir($concurrentDirectory = $this->path . '/' . $paths->path1 . '/' . $paths->path2 . '/', 0755, false)
                && !is_dir($concurrentDirectory)
            ) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        return $paths->fullPath;
    }


    /**
     * @param Int $expires
     * @return $this
     */
    public function expires(Int $expires)
    {
        if ($this->put && $this->key !== null) {
            touch($this->fullPath, time() + $expires);

            $this->put = false;

            $this->key = null;
        } else {
            $this->expires = $expires;
        }

        return $this;
    }


    public function minutes(Int $minutes)
    {
        return $this->expires($minutes * 60);
    }


    public function hours(Int $hours)
    {
        return $this->expires($hours * 3600);
    }


    public function day(Int $day)
    {
        return $this->expires($day * 3600 * 24);
    }


    private function existsExpires($paths)
    {
        if (file_exists($paths->fullPath)) {
            $mtime = filemtime($paths->fullPath);

            if ($mtime <= time() && $mtime > 0) {
                unlink($paths->fullPath);

                if ($this->file
                    ->dirIsEmpty($this->path . '/' . $paths->path1 . '/' . $paths->path2)
                ) {
                    rmdir($this->path . '/' . $paths->path1 . '/' . $paths->path2);

                    if ($this->file->dirIsEmpty($this->path . '/' . $paths->path1)) {
                        rmdir($this->path . '/' . $paths->path1);
                    }
                }
                return false;
            }
            return true;
        }
        return false;
    }


    private function getpaths($key)
    {
        $parts = array_slice(str_split($hash = sha1($key), 2), 0, 2);

        $fullPath = $this->path . '/' . $parts[0] . '/' . $parts[1] . '/' . $hash;

        return (object) array('path1' => $parts[0], 'path2' => $parts[1], 'fullPath' => $fullPath);
    }


    public function flush()
    {
        $this->flushDir($this->path);
    }


    private function flushDir($dir)
    {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->flushDir($file);

                rmdir($file);
            } else {
                unlink($file);
            }
        }
    }


    public function gc()
    {
        $directoryRead = glob($this->path . "/*");

        foreach ($directoryRead as $file) {
            $mtime = filemtime($file);

            if (is_file($file) && $mtime <= time() && $mtime > 0) {
                unlink($file);
            }
        }

        foreach ($directoryRead as $dir) {
            if (is_dir($dir) && $this->file->dirIsEmpty($dir)) {
                rmdir($dir);
            }
        }
    }


    public function __get($key)
    {
        return $this->get($key);
    }


    public function __call($method, $args)
    {
        throw new BadMethodCallException("Call to undefined method Cache::$method()");
    }


    public function close()
    {
        return true;
    }
}
