<?php

namespace Backend\Modules\Faq\Domain\Question;

use Common\Doctrine\Entity\Meta;
use Common\Locale;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="FaqQuestionTranslation")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class QuestionTranslation
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
     * @var Question
     *
     * @ORM\ManyToOne(
     *     targetEntity="Question",
     *     inversedBy="translations"
     * )
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="questionId", referencedColumnName="id", nullable=false, onDelete="CASCADE"),
     *   @ORM\JoinColumn(name="questionRevisionid", referencedColumnName="revisionId", nullable=false, onDelete="CASCADE")
     * })
     */
    private $questionEntity;

    /**
     * @var Locale
     *
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
    private $question;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $answer;

    public function __construct(
        Question $questionEntity,
        Locale $locale,
        Meta $meta,
        string $question,
        string $answer
    ) {
        $this->questionEntity->addTranslation($locale, $this);
        $this->questionEntity = $questionEntity;
        $this->locale = $locale;
        $this->meta = $meta;
        $this->question = $question;
        $this->answer = $answer;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getQuestionEntity(): Question
    {
        return $this->questionEntity;
    }

    public function getMeta(): Meta
    {
        return $this->meta;
    }

    public function getLocale(): Locale
    {
        return $this->locale;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public static function fromDataTransferObject(QuestionTranslationDataTransferObject $dataTransferObject): self
    {
        return new self(
            $dataTransferObject->questionEntity,
            $dataTransferObject->getLocale(),
            $dataTransferObject->meta,
            $dataTransferObject->question,
            $dataTransferObject->answer
        );
    }
}
