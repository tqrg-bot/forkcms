<?php

namespace Backend\Modules\Faq\Domain\Question;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class StatusDBALType extends StringType
{
    public function getName(): string
    {
        return 'faq_question_status';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Status
    {
        if ($value === null) {
            return null;
        }

        return new Status($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        return (string) $value;
    }
}
