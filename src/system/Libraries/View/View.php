<?php namespace TT\Libraries\View;

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
use TT\Engine\App;

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
            $this->data[ $key ] = $value;
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
        if ($errors = App::get('session')->flash('view-errors')) {
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

        $loader = new EdgeFileLoader(App::get('config')->get('view.files'));

        foreach (App::get('config')->get('view.file_extensions', []) as $file_extension) {
            $loader->addFileExtension($file_extension);
        }

        $edge = new Edge($loader, null, new EdgeFileCache(App::get('config')->get('view.cache_path')));

        if ($extensions = App::get('config')->get('view.extensions')) {
            foreach ($extensions as $extension) {
                if (new $extension instanceof EdgeExtensionInterface) {
                    $edge->addExtension(new $extension());
                }
            }
        }

        $content = $edge->render($this->file, $this->data);

        if ($this->minify === null) {
            $this->minify = App::get('config')->get('view.minify');
        }

        if ($this->minify) {
            $content  = preg_replace('/([\n]+)|([\s]{2})/', '', $content);
        }

        $this->reset();

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
