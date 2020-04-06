<?php namespace TT\Auth\Drivers;

use TT\Facades\Redis as R;
use TT\Http;
use TT\Redis;

class RedisAttemptDriver implements AttemptDriverInterface
{
    private $guard;

    private $redis;

    private $http;

    public function __construct(Redis $redis,Http $http,$guard)
    {
        $this->guard = $guard;
        $this->redis = $redis;
        $this->http = $http;
    }

    public function getAttemptsCountOrFail()
    {
        if (($result = $this->redis->get("AUTH_ATTEMPT_COUNT_".md5($this->http->ip().$this->guard)))) {
            return (object) array('count' => $result);
        }
        return false;
    }

    public function increment()
    {
        $count = $this->getAttemptsCountOrFail();

        $this->redis->setex(
            "AUTH_ATTEMPT_COUNT".md5($this->http->ip().$this->guard),
            60*60,
            $count ? $count->count+1 :1
        );
    }



    public function startLockTime($lockTime)
    {
        $expire = strtotime("+ {$lockTime} seconds");

        $this->redis->expire("AUTH_ATTEMPT_COUNT".md5($this->http->ip().$this->guard), $expire);

        $this->redis->setex("AUTH_ATTEMPT_EXPIRE".md5($this->http->ip().$this->guard), $expire, $expire);
    }


    public function deleteAttempt()
    {
        $this->redis->delete("AUTH_ATTEMPT_COUNT".md5($this->http->ip().$this->guard));
        $this->redis->delete("AUTH_ATTEMPT_EXPIRE".md5($this->http->ip().$this->guard));
    }



    public function expireTimeOrFail()
    {
        return $this->redis->get("AUTH_ATTEMPT_EXPIRE".md5($this->http->ip().$this->guard));
    }


    public function getRemainingSecondsOrFail()
    {
        if (($expireTime = $this->expireTimeOrFail())) {
            $remaining_seconds = $expireTime - time();

            if ($remaining_seconds > 0) {
                return $remaining_seconds;
            }
        }

        $this->deleteAttempt();

        return false;
    }
}
