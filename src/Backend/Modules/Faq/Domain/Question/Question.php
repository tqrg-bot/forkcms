<?php

namespace Backend\Modules\Faq\Domain\Question;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="FaqQuestion")
 * @ORM\Entity(repositoryClass="Backend\Modules\Faq\Domain\Question\Question\QuestionRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Question
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $sequence;

    /**
     * @var bool
     *
     * @ORM\Column(type="bool")
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

    public function __construct(
        string $sequence,
        string $visibleOnPhone,
        string $visibleOnTablet,
        bool $visibleOnDesktop
    ) {
        $this->sequence = $sequence;
        $this->visibleOnPhone = $visibleOnPhone;
        $this->visibleOnTablet = $visibleOnTablet;
        $this->visibleOnDesktop = $visibleOnDesktop;
    }

    public function getId(): int
    {
        return $this->id;
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
        if ($dataTransferObject->hasExistingQuestion()) {
            $question = $dataTransferObject->getQuestionEntity();
            $question->sequence = $dataTransferObject->sequence;
            $question->visibleOnPhone = $dataTransferObject->visibleOnPhone;
            $question->visibleOnTablet = $dataTransferObject->visibleOnTablet;
            $question->visibleOnDesktop = $dataTransferObject->visibleOnDesktop;

            return $question;
        }

        return new self(
            $dataTransferObject->sequence,
            $dataTransferObject->visibleOnPhone,
            $dataTransferObject->visibleOnTablet,
            $dataTransferObject->visibleOnDesktop
        );
    }
}
