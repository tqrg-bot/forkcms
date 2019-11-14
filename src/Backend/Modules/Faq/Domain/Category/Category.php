<?php

namespace Backend\Modules\Faq\Domain\Category;

use Backend\Core\Language\Language as BackendLanguage;
use Backend\Core\Language\Locale as BackendLocale;
use Backend\Modules\Faq\Domain\Question\Question;
use Common\Language;
use Common\Locale;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Frontend\Core\Language\Locale as FrontendLocale;

/**
 * @ORM\Table(name="FaqCategory")
 * @ORM\Entity(repositoryClass="Backend\Modules\Faq\Domain\Category\CategoryRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Category
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $sequence;

    /**
     * @var Collection|CategoryTranslation[]
     *
     * @ORM\OneToMany(
     *     targetEntity="CategoryTranslation",
     *     mappedBy="category",
     *     cascade={"persist", "merge", "remove", "detach"},
     *     orphanRemoval=true,
     *     indexBy="locale"
     * )
     */
    private $translations;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdOn;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $editedOn;

    /**
     * @var Collection|Question[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Backend\Modules\Faq\Domain\Question\Question",
     *     mappedBy="category",
     *     cascade={"persist", "merge", "remove", "detach"},
     *     orphanRemoval=true
     * )
     * @ORM\OrderBy({"sequence":"ASC"})
     */
    private $questions;

    public function __construct(
        int $sequence
    ) {
        $this->sequence = $sequence;
        $this->translations = new ArrayCollection();
        $this->questions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function changeSequence(int $sequence): void
    {
        $this->sequence = $sequence;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist(): void
    {
        $this->createdOn = new DateTime();
        $this->editedOn = new DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate(): void
    {
        $this->editedOn = new DateTime();
    }

    public function getCreatedOn(): DateTime
    {
        return $this->createdOn;
    }

    public function getEditedOn(): DateTime
    {
        return $this->editedOn;
    }

    public static function fromDataTransferObject(CategoryDataTransferObject $dataTransferObject): self
    {
        if ($dataTransferObject->hasCategoryEntity()) {
            $category = $dataTransferObject->getCategoryEntity();
            $category->sequence = $dataTransferObject->sequence;
        } else {
            $category = new self($dataTransferObject->sequence);
        }

        foreach ($dataTransferObject->translations as $translation) {
            $translation->category = $category;

            CategoryTranslation::fromDataTransferObject($translation);
        }

        return $category;
    }

    /**
     * @return Collection|CategoryTranslation[]
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(Locale $locale): CategoryTranslation
    {
        if ($this->translations->containsKey((string) $locale)) {
            return $this->translations->get((string) $locale);
        }

        throw CategoryTranslationNotFoundException::withLocale($locale);
    }

    public function addTranslation(Locale $locale, CategoryTranslation $categoryTranslation): void
    {
        $this->translations->set((string) $locale, $categoryTranslation);
    }

    /**
     * @return Collection|Question[]
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): void
    {
        if ($this->questions->contains($question)) {
            return;
        }

        $this->questions->add($question);
    }

    public function getCurrentTranslation(): CategoryTranslation
    {
        if (Language::get() === BackendLanguage::class) {
            return $this->getTranslation(BackendLocale::workingLocale());
        }

        return $this->getTranslation(FrontendLocale::frontendLanguage());
    }

    public function __toString(): string
    {
        return $this->getCurrentTranslation()->getName();
    }
}
