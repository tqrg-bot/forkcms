<?php

namespace Backend\Modules\Faq\Domain\Question;

use RuntimeException;
use Throwable;

final class IdAlreadySetException extends RuntimeException
{
    public function __construct($code = 0, Throwable $previous = null)
    {
        parent::__construct('The id has already been set', $code, $previous);
    }
}
