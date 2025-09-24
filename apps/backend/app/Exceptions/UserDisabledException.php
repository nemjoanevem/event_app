<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UserDisabledException extends HttpException
{
    public function __construct(string $message = null)
    {
        parent::__construct(423, $message ?: trans('errors.user_disabled'));
    }
}
