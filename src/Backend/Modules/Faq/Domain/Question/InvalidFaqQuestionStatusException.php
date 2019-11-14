<?php

namespace Backend\Modules\Faq\Domain\Question;

use UnexpectedValueException;

final class InvalidFaqQuestionStatusException extends UnexpectedValueException
{
    public static function withType(string $type): self
    {
        return new self("$type is not a valid Status");
    }
}
