<?php

namespace TT\Auth\Drivers;

use TT\Database\Builder;
use TT\Http;

class DatabaseAttemptDriver implements AttemptDriverInterface
{
    private $guard;

    private $builder;

    private $http;

    public function __construct(Builder $builder, Http $http, $guard)
    {
        $this->guard = $guard;
        $this->builder = $builder;
        $this->http = $http;
        $this->guard = $guard;
    }

    public function getAttemptsCountOrFail()
    {
        return $this->builder->table('attempts')->where([
            'ip' => $this->http->ip(),
            'guard' => $this->guard
        ])->first();
    }

    public function increment()
    {
        if ($this->getAttemptsCountOrFail()) {
            $this->builder->pdo()->query("UPDATE attempts SET count = count+1 WHERE ip ='{$this->http->ip()}' AND guard='{$this->guard}'");
        } else {
            $this->builder->query("INSERT INTO attempts SET ip = '{$this->http->ip()}',guard='{$this->guard}',count=1");
        }
    }


    public function startLockTime($lockTime)
    {
        $time = strtotime("+ {$lockTime} seconds");

        $this->builder->pdo()->query("UPDATE attempts SET expiredate = '{$time}' WHERE ip ='{$this->http->ip()}' AND guard='{$this->guard}'");
    }


    public function deleteAttempt()
    {
        $this->builder->pdo()->query("DELETE FROM attempts WHERE ip ='{$this->http->ip()}' AND guard='{$this->guard}'");
    }



    public function expireTimeOrFail()
    {
        $result = $this->builder->pdo()->query("SELECT expiredate FROM attempts WHERE ip='{$this->http->ip()}' AND guard='{$this->guard}'");

        if ($result->rowCount() > 0) {
            return $result->fetch()->expiredate;
        }

        return false;
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
