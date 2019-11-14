<?php

namespace Backend\Modules\Faq\Domain\Question;

use RuntimeException;

final class InvalidFaqQuestionStatusException extends RuntimeException
{
    public static function withType(string $type)
    {
        return new self("$type is not a valid Status");
    }
}
