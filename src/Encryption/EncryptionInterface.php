<?php

namespace TT\Encryption;


interface EncryptionInterface{

    public function encrypt($data);

    public function decrypt($data);
}