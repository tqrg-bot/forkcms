<?php

namespace Backend\Modules\Faq\Domain\Category;

use Backend\Core\Language\Language;
use Backend\Core\Language\Locale;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class CategoryDataTransferObject
{
    /**
     * @var Category|null
     */
    private $category;

    /**
     * @var int
     */
    public $sequence;

    /**
     * @var Collection|CategoryTranslationDataTransferObject[]
     */
    public $translations;

    public function __construct(Category $category = null)
    {
        $this->category = $category;
        $this->translations = $this->createTranslations();

        if ($category === null) {
            return;
        }

        $this->sequence = $category->getSequence();

        foreach ($category->getTranslations() as $categoryTranslation) {
            $this->translations->set(
                (string) $categoryTranslation->getLocale(),
                new CategoryTranslationDataTransferObject($categoryTranslation)
            );
        }
    }

    public function getId(): ?int
    {
        if ($this->hasCategoryEntity()) {
            return $this->category->getId();
        }

        return null;
    }

    /**
     * This method makes sure that even when new languages are added we will always show them in the form
     *
     * @return ArrayCollection
     */
    private function createTranslations(): ArrayCollection
    {
        $translations = new ArrayCollection();
        foreach (Language::getActiveLanguages() as $locale) {
            $translations->set(
                $locale,
                new CategoryTranslationDataTransferObject(
                    null,
                    Locale::fromString($locale)
                )
            );
        }

        return $translations;
    }

    public function hasCategoryEntity(): bool
    {
        return $this->category instanceof Category;
    }

    public function getCategoryEntity(): Category
    {
        return $this->category;
    }
}
