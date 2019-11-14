<?php

namespace Backend\Modules\Faq\Domain\Question;

use Backend\Modules\Faq\Domain\Category\Category;
use Common\Locale;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="FaqQuestion")
 * @ORM\Entity(repositoryClass="Backend\Modules\Faq\Domain\Question\QuestionRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Question
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $revisionId;

    /**
     * @var Status
     *
     * @ORM\Column(type="faq_question_status")
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $sequence;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $visibleOnPhone;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $visibleOnTablet;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $visibleOnDesktop;

    /**
     * @var Collection|QuestionTranslation[]
     *
     * @ORM\OneToMany(
     *     targetEntity="QuestionTranslation",
     *     mappedBy="questionEntity",
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
     * @var Category
     *
     * @ORM\ManyToOne(
     *     targetEntity="Backend\Modules\Faq\Domain\Category\Category",
     *     inversedBy="questions"
     * )
     */
    private $category;

    public function __construct(
        int $id,
        int $revisionId,
        Status $status,
        int $sequence,
        bool $visibleOnPhone,
        bool $visibleOnTablet,
        bool $visibleOnDesktop,
        Category $category
    ) {
        $this->id = $id;
        $this->revisionId = $revisionId;
        $this->status = $status;
        $this->sequence = $sequence;
        $this->visibleOnPhone = $visibleOnPhone;
        $this->visibleOnTablet = $visibleOnTablet;
        $this->visibleOnDesktop = $visibleOnDesktop;
        $this->category = $category;
        $this->translations = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRevisionId(): int
    {
        return $this->revisionId;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function getVisibleOnPhone(): bool
    {
        return $this->visibleOnPhone;
    }

    public function getVisibleOnTablet(): bool
    {
        return $this->visibleOnTablet;
    }

    public function isVisibleOnDesktop(): bool
    {
        return $this->visibleOnDesktop;
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

    public static function fromDataTransferObject(QuestionDataTransferObject $dataTransferObject): self
    {
        $question = new self(
            $dataTransferObject->getId(),
            $dataTransferObject->revisionId,
            $dataTransferObject->status,
            $dataTransferObject->sequence,
            $dataTransferObject->visibleOnPhone,
            $dataTransferObject->visibleOnTablet,
            $dataTransferObject->visibleOnDesktop,
            $dataTransferObject->getCategory()
        );

        foreach ($dataTransferObject->translations as $translation) {
            $translation->questionEntity = $question;

            QuestionTranslation::fromDataTransferObject($translation);
        }

        return $question;
    }

    /**
     * @return Collection|QuestionTranslation[]
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function getTranslation(Locale $locale): QuestionTranslation
    {
        if ($this->translations->containsKey($locale)) {
            return $this->translations->get($locale);
        }

        throw QuestionTranslationNotFoundException::withLocale($locale);
    }

    public function addTranslation(Locale $locale, QuestionTranslation $questionTranslation): void
    {
        $this->translations->set($locale, $questionTranslation);
    }
}
