<?php

namespace TT\View;

/**
 * @package  TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage  Library
 * @category   View
 */

use TT\Exceptions\ViewException;
use Windwalker\Edge\Cache\EdgeFileCache;
use Windwalker\Edge\Loader\EdgeFileLoader;
use Windwalker\Edge\Extension\EdgeExtensionInterface;
use Windwalker\Edge\Edge;
use TT\Facades\Session;
use TT\Facades\Config;

class View
{
    protected $file;

    protected $data = [];

    protected $minify;


    public function render(String $file, $data = [])
    {
        $this->file = $file;
        $this->data = array_merge($this->data, $data);
        return $this;
    }


    public function file(String $file)
    {
        $this->file = $file;
        return $this;
    }


    public function data($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }


    public function minify(Bool $minify = true)
    {
        $this->minify = $minify;
        return $this;
    }


    protected function withFlashData()
    {
        if ($errors = Session::flash('view-errors')) {
            $errors = new Errors($errors);
        }

        if (!isset($this->data['errors'])) {
            $this->data['errors'] = $errors ?: new Errors;
        }
    }


    protected function reset()
    {
        $this->file    = null;
        $this->data    = [];
        $this->content = null;
        $this->minify  = null;
    }


    protected function finishRender()
    {
        if ($this->file === null) {
            throw new ViewException('View File not found');
        }

        $this->withFlashData();

        $loader = new EdgeFileLoader((array) Config::get('view.files'));

        foreach (Config::get('view.file_extensions', []) as $file_extension) {
            $loader->addFileExtension($file_extension);
        }

        $edge = new Edge(
            $loader,
            null,
            new EdgeFileCache(
                Config::get('view.cache_path')
            )
        );

        if ($extensions = Config::get('view.extensions')) {
            foreach ($extensions as $extension) {
                $object = new $extension;
                if ($object instanceof EdgeExtensionInterface) {
                    $edge->addExtension($object);
                }
            }
        }

        $content = $this->checkMinify(
            $edge->render($this->file, $this->data)
        );


        $this->reset();

        return $content;
    }


    private function checkMinify($content)
    {
        if ($this->minify === null) {
            $this->minify = Config::get('view.minify');
        }

        if ($this->minify) {
            $search = array('/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s');

            $replace = array('>', '<', '\\1');

            $content  = preg_replace($search, $replace, $content);
        }

        return $content;
    }


    /**
     * @return string|string[]|null
     * @throws ViewException
     */
    public function getContent()
    {
        return $this->finishRender();
    }


    /**
     * @return string
     * @throws ViewException
     */
    public function __toString()
    {
        return (string) $this->finishRender();
    }
}
