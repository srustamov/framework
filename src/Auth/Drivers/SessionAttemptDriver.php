<?php

namespace TT\Auth\Drivers;

use TT\Session\Session;

class SessionAttemptDriver implements AttemptDriverInterface
{
    private $session;

    private $guard;

    public function __construct(Session $session, $guard)
    {
        $this->session = $session;

        $this->guard = $guard;
    }

    public function getAttemptsCountOrFail()
    {
        if ($count = $this->session->get('AUTH_ATTEMPT_COUNT_' . $this->guard)) {
            return (object) array('count' => $count);
        }
        return false;
    }

    public function increment()
    {
        if ($this->getAttemptsCountOrFail()) {
            $this->session->set('AUTH_ATTEMPT_COUNT_' . $this->guard, function ($session) {
                return $session->get('AUTH_ATTEMPT_COUNT_' . $this->guard) + 1;
            });
        } else {
            $this->session->set('AUTH_ATTEMPT_COUNT_' . $this->guard, 1);
        }
    }



    public function startLockTime($lockTime)
    {
        $this->session->set('AUTH_ATTEMPT_EXPIRE_' . $this->guard, strtotime("+ {$lockTime} seconds"));
    }


    public function deleteAttempt()
    {
        $this->session->delete(array('AUTH_ATTEMPT_COUNT_' . $this->guard, 'AUTH_ATTEMPT_EXPIRE_' . $this->guard));
    }



    public function expireTimeOrFail()
    {
        return $this->session->get('AUTH_ATTEMPT_EXPIRE_' . $this->guard);
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
