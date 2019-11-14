<?php

namespace Backend\Modules\Faq\Domain\Category;

use Common\Doctrine\Entity\Meta;
use Common\Locale;
use Symfony\Component\Validator\Constraints as Assert;

class CategoryTranslationDataTransferObject
{
    /** @var CategoryTranslation */
    private $categoryTranslation;

    /**
     * @var Category|null
     */
    public $category;

    /**
     * @var Locale
     */
    protected $locale;

    /**
     * @var Meta
     */
    public $meta;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="err.FieldIsRequired")
     */
    public $name;

    public function __construct(CategoryTranslation $categoryTranslation = null, Locale $locale = null)
    {
        if ($categoryTranslation === null && $locale instanceof Locale) {
            $this->locale = $locale;

            return;
        }

        $this->categoryTranslation = $categoryTranslation;
        $this->meta = $categoryTranslation->getMeta();
        $this->category = $categoryTranslation->getCategory();
        $this->locale = $categoryTranslation->getLocale();
        $this->name = $categoryTranslation->getName();
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public function getId(): ?int
    {
        if ($this->category === null) {
            return null;
        }

        return $this->category->getId();
    }

    public function getCategoryTranslationEntity(): CategoryTranslation
    {
        return $this->categoryTranslation;
    }

    public function hasCategoryTranslationEntity(): bool
    {
        return $this->categoryTranslation instanceof CategoryTranslation;
    }
}
