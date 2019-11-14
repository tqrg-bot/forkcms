<?php

namespace Backend\Modules\Faq\Domain\Category;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CategoryTranslationNotFoundException extends NotFoundHttpException
{
    public static function withLocale(string $locale): self
    {
        return new self("No translation was found for the locale $locale");
    }
}
