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
            return;
        }

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
}
