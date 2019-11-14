<?php

namespace Backend\Modules\Faq\Domain\Question;

use Common\Core\Model;
use Common\Locale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;

class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    public function add(Question $question): void
    {
        $this->getEntityManager()->persist($question);
    }

    public function remove(Question $question): void
    {
        $this->getEntityManager()->remove($question);
    }

    public function findBySlugAndLocale(string $slug, Locale $locale): ?Question
    {
        try {
            return $this
                ->createQueryBuilder('q')
                ->select('q, qt, m, c')
                ->innerJoin('q.translations', 'qt', Join::WITH, 'qt.locale = :locale')
                ->innerJoin('qt.meta', 'm', Join::WITH, 'm.url = :slug')
                ->innerJoin('q.category', 'c')
                ->setParameter('slug', $slug)
                ->setParameter('locale', $locale)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException | NonUniqueResultException $resultException) {
            return null;
        }
    }

    public function getUrl(string $url, Locale $locale, int $id = null): string
    {
        $query = $this
            ->createQueryBuilder('q')
            ->select('COUNT(q)')
            ->innerJoin('q.translations', 'qt', Join::WITH, 'qt.locale = :locale')
            ->innerJoin('qt.meta', 'm', Join::WITH, 'm.url = :url')
            ->setParameter('url', $url)
            ->setParameter('locale', $locale);

        if ($id !== null) {
            $query
                ->andWhere('q.id != :id')
                ->setParameter('id', $id);
        }

        if ((int)$query->getQuery()->getSingleScalarResult() === 0) {
            return $url;
        }

        return $this->getUrl(Model::addNumber($url), $locale, $id);
    }
}
