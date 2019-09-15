<?php

namespace TT\Libraries\Encryption;


interface EncryptionInterface{

    public function encrypt($data);

    public function decrypt($data);
}