<?php

namespace AK\Covid;

use Exception;
use Silex\Application as BaseApplication;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Application extends BaseApplication
{

    public function __construct(array $values = array())
    {
        parent::__construct($values);

        if (!file_exists($this['credentials_file'])) {
            return $this->abort(500, 'Credentials file is absent');
        }

        if (!file_exists($this['token_file'])) {
            return $this->abort(500, 'Token file is absent');
        }

        $token = (array)json_decode(file_get_contents($this['token_file']), true);

        if (empty($token)) {
            return $this->abort(500, 'Token file is corrupted');
        }

        $this['token'] = $token;
    }
}
