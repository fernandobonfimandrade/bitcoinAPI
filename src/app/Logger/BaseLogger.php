<?php 

namespace App\Logger;


use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class BaseLogger extends Logger
{
    public function __construct()
    {
        $name = 'logger';
        parent::__construct($name);
    }

    public function init($path)
    {
        $this->pushHandler(new StreamHandler(storage_path($path)), Logger::INFO);
    }

}
