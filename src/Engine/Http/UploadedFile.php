<?php namespace TT\Engine\Http;

/**
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link    https://github.com/srustamov/TT
 */



class UploadedFile extends \SplFileInfo
{
    private $files;

    private $name;

    private $size;

    private $error;

    private $uploadError;

    private $mimeType;


    /**
     * UploadedFile constructor.
     * @param array $files
     */
    public function __construct(array $files)
    {
        $this->files = $files;
    }


    /**
     * @param $name
     * @return $this|bool
     */
    public function get($name)
    {
        if (isset($this->files[$name])) {
            $file = $this->files[$name];

            $this->error = $file['error'] ? : UPLOAD_ERR_OK;

            $this->setName($file['name']);
    
            $this->size = $file['size'];
    
            $this->mimeType = $file['type'];
    
            parent::__construct($file['tmp_name']);

            return $this;
        }

        return false;
    }


    /**
     * @return bool
     */
    public function isValid(): bool
    {
        $isOk = $this->error === UPLOAD_ERR_OK;

        return $isOk && is_uploaded_file($this->getPathname());
    }


    /**
     * @param $name
     */
    public function setName($name): void
    {
        $originalName = str_replace('\\', '/', $name);
        $position     = strrpos($originalName, '/');
        $originalName = false === $position ? $originalName : substr($originalName, $position + 1);

        $this->name =  $originalName;
    }


    /**
     * @return mixed
     */
    public function size()
    {
        return $this->size;
    }

    /**
     * @return mixed
     */
    public function name()
    {
        return $this->name;
    }


    /**
     * @return string
     */
    public function extension(): string
    {
        return $this->getExtension();
    }


    /**
     * @return mixed
     */
    public function mimeType()
    {
        return $this->mimeType;
    }

    /**
     * @return mixed
     */
    public function uploadErrorMessage()
    {
        return $this->uploadError;
    }


    /**
     * @param $target
     * @param string|null $name
     * @return bool
     */
    public function move($target, string $name = null): bool
    {
        if ($this->isValid()) {
            $target = rtrim($target, '/').'/';

            $name = $name ?? $this->name();

            if (!mkdir($target, 0777, true) && !is_dir($target)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $target));
            }

            if (!@move_uploaded_file($this->getRealPath(), $target.$name)) {
                $error = error_get_last();

                $this->uploadError = $error['message'] ?? '';

                return false;
            }

            return true;
        }

        return false;
    }
}
