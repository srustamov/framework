<?php

namespace TT\Auth;

/**
 * @package TT
 * @author Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage  Library
 * @category  Authentication
 */


use Exception;
use RuntimeException;
use TT\Database\Orm\Model;
use InvalidArgumentException;
use TT\Cookie;
use TT\Engine\App;
use TT\Hash;
use TT\Session\Session;
use Carbon\CarbonInterval;

class Authentication implements \ArrayAccess, \JsonSerializable
{
    protected $message;

    protected $throttle;

    /**@var Drivers\AttemptDriverInterface */
    protected $attemptDriver;

    protected $lockTime;

    protected $maxAttempts;

    protected $attemptDriverName;

    protected $attemptDrivers = [
        'session' => Drivers\SessionAttemptDriver::class,
        'redis' => Drivers\RedisAttemptDriver::class,
        'database' => Drivers\DatabaseAttemptDriver::class,
    ];

    protected $passwordName;

    protected $guard = 'default';

    /**@var array*/
    protected $booted;

    /**@var array*/
    protected $config;

    protected $user;

    /**@var Model*/
    private $model;

    private $session;

    private $cookie;

    private $hash;

    private $secret;

    private $app;


    public function __construct(
        App $app,
        Session $session,
        Cookie $cookie,
        Hash $hash,
        array $config = null,
        string $secret = null
    ) {
        $this->session = $session;
        $this->cookie = $cookie;
        $this->hash = $hash;
        $this->app = $app;

        if (!$config) {
            $config = $this->app['config']->get('authentication.guards');
        }

        if (!$secret) {
            $this->secret = $this->app['config']->get('app.key');
        }

        $this->config = $config;


        $this->guardBootIfNotBoot();
    }


    protected function guardBootIfNotBoot($guard = null)
    {
        $guard = $guard ?? $this->guard;

        if (!isset($this->booted[$guard])) {
            $config = $this->config[$guard];
            if (class_exists($config['model'])) {
                $this->model[$guard] = new $config['model']();
                if (!($this->model[$guard] instanceof Model)) {
                    throw new RuntimeException('Authentication model not instance Database\Model');
                }
            } else {
                throw new RuntimeException(sprintf('Authentication model[%s] not found', $config['model']));
            }

            $this->throttle[$guard] = $config['throttle']['enable'] ?? false;

            $this->passwordName[$guard] = $config['password_name'] ?? 'password';

            if ($this->throttle[$guard]) {
                $this->attemptDriverName[$guard] = $config['throttle']['driver'];
                $this->maxAttempts[$guard] = $config['throttle']['max_attempts'];
                $this->lockTime[$guard] = $config['throttle']['lock_time'];
            }

            $this->booted[$guard] = true;
        }
    }


    public function guard(string $guard = null)
    {
        if ($guard !== null) {
            $this->guard = $guard;
            $this->guardBootIfNotBoot();
            return $this;
        }
        return $this->guard;
    }


    protected function beforeLogin(Model $user, $guard): Bool
    {
        return true;
    }


    protected function afterLogin(Model $user, $guard)
    {
        //return true;
    }



    /**
     * @param Model $user
     * @return self
     */
    public function login(Model $user)
    {
        $this->session->set(md5($this->guard) . '-id', $user->getAttribute($user->getPrimaryKey()));
        $this->session->set(md5($this->guard) . '-login', true);

        $this->user[$this->guard] = $user;

        return $this;
    }

    /**
     * @param Model $user
     * @param string|null $guard
     * @return object
     */
    public function user(string $guard = null)
    {
        $guard = $guard ?? $this->guard;

        if (!isset($this->user[$guard])) {
            if ($id = $this->session->get(md5($guard) . '-id')) {
                $this->user[$guard] = $this->model[$guard]->find($id);
            }
        }

        return $this->user[$guard] ?? null;
    }


    protected function getPasswordName(): string
    {
        return $this->passwordName[$this->guard];
    }


    /**
     * @param array $credentials
     * @param bool $remember
     * @param bool $once
     * @return bool
     * @throws Exception
     */
    public function attempt(array $credentials, bool $remember = false, bool $once = false): bool
    {
        if ($this->throttle[$this->guard]) {

            $this->setAttemptDriver();
            if (
                ($attempts = $this->attemptDriver[$this->guard]->getAttemptsCountOrFail()) &&
                $attempts->count >= $this->maxAttempts[$this->guard] &&
                $seconds = $this->attemptDriver[$this->guard]->getRemainingSecondsOrFail()
            ) {
                $this->message[$this->guard] = $this->getLockMessage($seconds);
                return false;
            }
        }

        if (isset($credentials[$this->getPasswordName()])) {
            $password = $credentials[$this->getPasswordName()];
            unset($credentials[$this->getPasswordName()]);
        } else {
            throw new InvalidArgumentException('Auth ' . $this->getPasswordName() . ' not found');
        }

        if ($user = $this->model[$this->guard]->find($credentials)) {
            if ($this->hash->check($password, $user->password)) {
                if ($this->throttle[$this->guard]) {
                    $this->attemptDriver[$this->guard]->deleteAttempt();
                }
                if ($remember) {
                    $this->setRemember($user);
                }
                if ($this->beforeLogin($user, $this->guard)) {
                    if (!$once) {
                        $this->setSession($user);
                    }
                    $this->afterLogin($user, $this->guard);
                    return true;
                }
                return false;
            }
        }
        if ($this->throttle[$this->guard]) {
            $this->attemptDriver[$this->guard]->increment();
            $remaining = $this->maxAttempts[$this->guard] - $this->attemptDriver[$this->guard]->getAttemptsCountOrFail()->count;
            if ($remaining === 0) {
                $this->attemptDriver[$this->guard]->startLockTime($this->lockTime[$this->guard]);
            }
        }

        $this->message[$this->guard] = $this->getFailMessage($remaining ?? null);

        return false;
    }


    public function once(array $credentials)
    {
        return $this->attempt($credentials, false, true);
    }



    /**
     * @return bool
     */
    public function check(): bool
    {
        if (isset($this->user[$this->guard]) && $this->user[$this->guard] instanceof  Model) {
            return true;
        }

        if ($this->session->get(md5($this->guard) . '-login') === true) {
            if ($this->user() && ($id = $this->session->get(md5($this->guard) . '-id'))) {
                return $id && $this->user[$this->guard]->id === $id;
            }
            return false;
        }

        if ($user = $this->remember()) {
            if ($this->beforeLogin($user, $this->guard)) {
                $this->setSession($user);
                $this->user[$this->guard] = $user;
                return true;
            }
        }

        return false;
    }


    /**
     * @return bool
     */
    public function guest(): bool
    {
        return !$this->check();
    }


    /**
     * @return bool|Model
     */
    public function remember()
    {
        if ($this->cookie->has(md5($this->guard) . '-remember')) {
            $token = $this->cookie->get(md5($this->guard) . '-remember');
            return $this->model[$this->guard]->find(['remember_token' => base64_decode($token)]);
        }
        return false;
    }


    /**
     * @param $user
     * @return $this
     */
    public function setRemember(Model $user): self
    {
        if ($user->remember_token) {
            $this->cookie->set(
                md5($this->guard) . '-remember',
                base64_encode($user->remember_token),
                3600 * 24 * 30
            );
        } else {
            $token = hash_hmac(
                'sha256',
                $user->email . $user->name,
                $this->secret
            );

            $this->cookie->set(
                md5($this->guard) . '-remember',
                base64_encode($token),
                3600 * 24 * 30
            );

            $user->remember_token = $token;

            $user->save();
        }

        return $this;
    }


    public function getMessage()
    {
        return $this->message[$this->guard];
    }


    protected function setSession(Model $user)
    {
        $this->session->set(md5($this->guard) . '-id', $user->id);
        $this->session->set(md5($this->guard) . '-login', true);
        return $this;
    }


    public function logout()
    {
        $this->user[$this->guard] = null;

        try {
            $this->session->delete(md5($this->guard) . '-id');
            $this->session->delete(md5($this->guard) . '-login');
            if ($this->cookie->has(md5($this->guard) . '-remember')) {
                $this->cookie->forget(md5($this->guard) . '-remember');
            }
        } catch (Exception $e) {
            $this->session->destroy();
        }
        return $this;
    }



    /**
     * @param $remaining
     * @return mixed
     * @throws Exception
     */
    protected function getFailMessage($remaining = null)
    {
        $message =  lang('auth.incorrect');
        if ($remaining !== null) {
            $message .= lang('auth.remaining', array('remaining' => $remaining));
        }
        return $message;
    }


    /**
     * @param $seconds
     * @return mixed
     * @throws Exception
     */
    protected function getLockMessage($seconds)
    {
        CarbonInterval::setLocale(lang()->getLocale());

        return lang('auth.many_attempts.text', [
            'time' => CarbonInterval::seconds($seconds)->cascade()->forHumans()
        ]);
    }

    /**
     * @return void
     */
    protected function setAttemptDriver(): void
    {
        if (array_key_exists($this->attemptDriverName, $this->attemptDrivers)) {
            $this->attemptDriver[$this->guard] = $this->app->make(
                $this->attemptDrivers[$this->attemptDriverName],
                $this->guard
            );
        } else {
            throw new RuntimeException('Attempt Driver not found !');
        }
    }


    public function __get($key)
    {
        return $this->user[$this->guard][$key] ?? null;
    }


    public function __set($key, $value)
    {
        $this->user[$this->guard][$key] = $value;

        return $this;
    }


    public function __call($method, $args)
    {
        if ($this->check()) {
            return $this->user()->{$method};
        }
        return false;
    }


    public function __toString()
    {
        return $this->message[$this->guard] ?? '';
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset): bool
    {
        if ($this->check()) {
            return array_key_exists($offset, $this->user[$this->guard]);
        }
        return false;
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        if ($this->check()) {
            return $this->user[$this->guard][$offset];
        }
        return null;
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        if ($this->check()) {
            $this->user[$this->guard][$offset] = $value;
        }
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        if ($this->check()) {
            unset($this->user[$this->guard][$offset]);
        }
    }


    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        if ($this->check()) {
            return json_encode($this->user());
        }
    }
}
