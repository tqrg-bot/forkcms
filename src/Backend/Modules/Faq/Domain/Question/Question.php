<?php

namespace Backend\Modules\Faq\Domain\Question;

use DateTime;
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
        int $id,
        int $revisionId,
        Status $status,
        int $sequence,
        bool $visibleOnPhone,
        bool $visibleOnTablet,
        bool $visibleOnDesktop
    ) {
        $this->id = $id;
        $this->revisionId = $revisionId;
        $this->status = $status;
        $this->sequence = $sequence;
        $this->visibleOnPhone = $visibleOnPhone;
        $this->visibleOnTablet = $visibleOnTablet;
        $this->visibleOnDesktop = $visibleOnDesktop;
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
            $question->revisionId = $dataTransferObject->revisionId;
            $question->status = $dataTransferObject->status;
            $question->sequence = $dataTransferObject->sequence;
            $question->visibleOnPhone = $dataTransferObject->visibleOnPhone;
            $question->visibleOnTablet = $dataTransferObject->visibleOnTablet;
            $question->visibleOnDesktop = $dataTransferObject->visibleOnDesktop;

            return $question;
        }

        return new self(
            $dataTransferObject->getId(),
            $dataTransferObject->revisionId,
            $dataTransferObject->status,
            $dataTransferObject->sequence,
            $dataTransferObject->visibleOnPhone,
            $dataTransferObject->visibleOnTablet,
            $dataTransferObject->visibleOnDesktop
        );
    }
}
