<?php

namespace Backend\Modules\Faq\Domain\Question;

class QuestionDataTransferObject
{
    /**
     * @var Question
     */
    private $questionEntity;

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    public $revisionId;

    /**
     * @var Status
     */
    public $status;

    /**
     * @var int
     */
    public $sequence;

    /**
     * @var bool
     */
    public $visibleOnPhone;

    /**
     * @var bool
     */
    public $visibleOnTablet;

    /**
     * @var bool
     */
    public $visibleOnDesktop;

    public function __construct(Question $question = null)
    {
        $this->questionEntity = $question;

        if (!$this->hasExistingQuestion()) {
            $this->revisionId = 1;
            $this->status = Status::active();
            $this->visibleOnPhone = true;
            $this->visibleOnTablet = true;
            $this->visibleOnDesktop = true;

            return;
        }

        $this->setId($this->questionEntity->getId());
        $this->revisionId = $this->questionEntity->getRevisionId();
        $this->status = $this->questionEntity->getStatus();
        $this->id = $this->questionEntity->getId();
        $this->sequence = $this->questionEntity->getSequence();
        $this->visibleOnPhone = $this->questionEntity->getVisibleOnPhone();
        $this->visibleOnTablet = $this->questionEntity->getVisibleOnTablet();
        $this->visibleOnDesktop = $this->questionEntity->isVisibleOnDesktop();
    }

    public function getQuestionEntity(): Question
    {
        return $this->questionEntity;
    }

    public function hasExistingQuestion(): bool
    {
        return $this->questionEntity instanceof Question;
    }

    public function setId(int $id): void
    {
        if ($this->id !== null) {
            throw new IdAlreadySetException();
        }

        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
