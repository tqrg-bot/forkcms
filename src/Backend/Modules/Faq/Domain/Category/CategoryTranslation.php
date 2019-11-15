<?php

namespace Backend\Modules\Faq\Domain\Category;

use Common\Doctrine\Entity\Meta;
use Common\Locale;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="FaqCategoryTranslation")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class CategoryTranslation
{
    /**
     * @var Category
     *
     * @ORM\Id
     * @ORM\ManyToOne(
     *     targetEntity="Category",
     *     inversedBy="translations"
     * )
     */
    private $category;

    /**
     * @var Locale
     *
     * @ORM\Id
     * @ORM\Column(type="locale")
     */
    private $locale;

    /**
     * @var Meta
     *
     * @ORM\OneToOne(targetEntity="Common\Doctrine\Entity\Meta", cascade={"persist", "remove"})
     */
    private $meta;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $name;

    public function __construct(
        Category $category,
        Locale $locale,
        Meta $meta,
        string $name
    ) {
        $this->category = $category;
        $this->category->addTranslation($locale, $this);
        $this->locale = $locale;
        $this->meta = $meta;
        $this->name = $name;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getMeta(): Meta
    {
        return $this->meta;
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public static function fromDataTransferObject(CategoryTranslationDataTransferObject $dataTransferObject): self
    {
        if ($dataTransferObject->hasCategoryTranslationEntity()) {
            $categoryTranslation = $dataTransferObject->getCategoryTranslationEntity();
            $categoryTranslation->category = $dataTransferObject->category;
            $categoryTranslation->locale = $dataTransferObject->getLocale();
            $categoryTranslation->meta = $dataTransferObject->meta;
            $categoryTranslation->name = $dataTransferObject->name;

            return $categoryTranslation;
        }

        return new self(
            $dataTransferObject->category,
            $dataTransferObject->getLocale(),
            $dataTransferObject->meta,
            $dataTransferObject->name
        );
    }
}
