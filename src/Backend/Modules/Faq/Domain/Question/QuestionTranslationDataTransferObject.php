<?php

namespace Backend\Modules\Faq\Domain\Question;

use Common\Doctrine\Entity\Meta;
use Common\Locale;
use Symfony\Component\Validator\Constraints as Assert;

class QuestionTranslationDataTransferObject
{
    /**
     * @var Question|null
     */
    public $questionEntity;

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
    public $question;

    /**
     * @var string
     *
     * @Assert\NotBlank(message="err.FieldIsRequired")
     */
    public $answer;

    public function __construct(QuestionTranslation $questionTranslation = null)
    {
        if ($questionTranslation === null) {
            return;
        }

        $this->meta = clone $questionTranslation->getMeta();
        $this->questionEntity = $questionTranslation->getQuestionEntity();
        $this->locale = $questionTranslation->getLocale();
        $this->question = $questionTranslation->getQuestion();
        $this->answer = $questionTranslation->getAnswer();
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public function getId(): ?int
    {
        if ($this->questionEntity === null) {
            return null;
        }

        return $this->questionEntity->getId();
    }
}
