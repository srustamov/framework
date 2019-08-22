<?php namespace TT\Libraries\Auth\Drivers;

interface AttemptDriverInterface
{
    public function __construct(string $guard);

    public function getAttemptsCountOrFail();

    public function increment();

    public function startLockTime($lockTime);

    public function deleteAttempt();

    public function expireTimeOrFail();

    public function getRemainingSecondsOrFail();
}
