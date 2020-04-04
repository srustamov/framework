<?php

namespace TT;

use FilesystemIterator;
use RuntimeException;
use const LOCK_EX;

/**
 * @package    TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage    Library
 * @category    Files
 */
class File
{
    /**
     * @param $path
     * @return bool
     */
    public function create($path): bool
    {
        return touch($path);
    }


    /**
     * @param String $fileAndMode
     * @param callable|null $callback
     * @return bool|resource
     */
    public function open(String $fileAndMode, callable $callback = null)
    {
        if (strpos('|', $fileAndMode)) {
            [$file, $mode] = explode('|', $fileAndMode, 2);
        } else {
            [$file, $mode] = array($fileAndMode, 'r+');
        }


        if ($callback === null) {
            return fopen($file, $mode);
        }

        return $callback(fopen($file, $mode), $this);
    }


    /**
     * @param $file
     * @return bool
     */
    public function close($file): bool
    {
        return is_resource($file) ? fclose($file) : false;
    }


    /**
     * @param $dir
     * @return bool
     */
    public function dirIsEmpty($dir): bool
    {
        $iterator = new FilesystemIterator($dir);

        return !$iterator->valid();
    }


    /**
     * @param $path
     * @return false|int
     */
    public function size($path)
    {
        return filesize($path);
    }


    /**
     * @param $path
     * @return false|int
     */
    public function lastModifiedTime($path)
    {
        return filemtime($path);
    }


    /**
     * @param $file
     * @return bool
     */
    public function isImage($file): Bool
    {
        if (isset($file['tmp_name'])) {
            return @getimagesize($file['tmp_name']) ? true : false;
        }

        return @getimagesize($file) ? true : false;
    }


    /**
     * @param $pathname
     * @param int $mode
     * @param bool $recursive
     * @return bool
     */
    public function setDir($pathname, $mode = 0755, $recursive = false): Bool
    {
        return mkdir($pathname, $mode, $recursive);
    }

    /**
     * @param $pathname
     * @return bool
     */
    public function deleteDirectory($pathname): Bool
    {
        if (!$this->isDir($pathname)) {
            return false;
        }
        return rmdir($pathname);
    }

    /**
     * @param $pathname
     * @return bool
     */
    public function isDir($pathname): Bool
    {
        return is_dir($pathname);
    }

    /**
     * @param string $directory
     */
    public function rmdir_r(String $directory): void
    {
        if (is_dir($directory)) {
            $this->flashDir($directory);

            rmdir($directory);
        }
    }


    /**
     * @param $directory
     */
    public function flashDir($directory): void
    {
        foreach (glob($directory . '/*') as $file) {
            if (is_dir($file)) {
                $this->flashDir($file);
            } else {
                unlink($file);
            }
        }
    }


    /**
     * @param $file
     * @param bool $once
     * @return mixed
     */
    public function import($file, $once = true)
    {
        if ($this->exists($file)) {
            if ($once) {
                return (require_once $file);
            }

            return (require $file);
        }

        throw new RuntimeException("File not found.Path: ({$file})");
    }

    /**
     * @param $filename
     * @return bool
     */
    public function exists($filename): Bool
    {
        return file_exists($filename);
    }

    /**
     * @param $file
     * @return mixed
     */
    public function importOnce($file)
    {
        if ($this->exists($file)) {
            return (require_once $file);
        }

        throw new RuntimeException("File not found.Path: ({$file})");
    }

    /**
     * @param $path
     * @param $content
     * @param bool $lock
     * @return bool|int
     */
    public function write($path, $content, $lock = false)
    {
        if (is_resource($path)) {
            $return = fwrite($path, $content);

            if ($lock) {
                flock($path, LOCK_EX);
            }

            fclose($path);

            return $return;
        }

        return file_put_contents($path, $content, $lock ? LOCK_EX : 0);
    }

    /**
     * @param $path
     * @return bool
     */
    public function is($path): Bool
    {
        return is_file($path);
    }

    /**
     * @param $file
     * @param $data
     * @return bool|int
     */
    public function append($file, $data)
    {
        return file_put_contents($file, $data, FILE_APPEND);
    }

    /**
     * @param String $file
     * @param $content
     * @return bool|int
     */
    public function prepend(String $file, $content)
    {
        return file_put_contents($file, $content . $this->get($file));
    }

    /**
     * @param $path
     * @return false|string
     */
    public function get($path)
    {
        if ($this->is($path)) {
            return file_get_contents($path);
        }
        throw new RuntimeException('File does not exist at path ' . $path);
    }

    /**
     * @param $path
     * @param null $mode
     * @return bool|string
     */
    public function chmod($path, $mode = null)
    {
        if ($mode !== null) {
            return chmod($path, $mode);
        }
        return substr(fileperms($path), -4);
    }


    /**
     * @param $files
     * @return bool
     */
    public function delete($files): Bool
    {
        $_files = is_array($files) ? $files : func_get_args();

        $error = 0;

        foreach ($_files as $file) {
            if (!@unlink($file)) {
                $error++;
            }
        }
        return !($error > 0);
    }


    /**
     * @param $path
     * @param $target
     * @return bool
     */
    public function move($path, $target): Bool
    {
        return rename($path, $target);
    }


    /**
     * @param $path
     * @param $target
     * @return bool
     */
    public function copy($path, $target): Bool
    {
        return copy($path, $target);
    }


    /**
     * @param $path
     * @return false|string
     */
    public function type($path)
    {
        return filetype($path);
    }


    /**
     * @param $path
     * @return mixed
     */
    public function mimeType($path)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }


    /**
     * @param $path
     * @return mixed
     */
    public function getName($path)
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }


    /**
     * @param $path
     * @return mixed
     */
    public function getExtension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * @param $path
     * @return mixed
     */
    public function basename($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }
}
