<?php

namespace Backend\Modules\Faq\Domain\Category;

use Common\Core\Model;
use Common\Locale;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;

class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function add(Category $category): void
    {
        $this->getEntityManager()->persist($category);
    }

    public function remove(Category $category): void
    {
        $this->getEntityManager()->remove($category);
    }

    public function findBySlugAndLocale(string $slug, Locale $locale): ?Category
    {
        try {
            return $this
                ->createQueryBuilder('c')
                ->select('c, ct, cm, q, qt, qm')
                ->innerJoin('c.translations', 'ct', Join::WITH, 'ct.locale = :locale')
                ->innerJoin('ct.meta', 'cm', Join::WITH, 'cm.url = :slug')
                ->innerJoin('c.questions', 'q')
                ->innerJoin('q.translations', 'qt', Join::WITH, 'qt.locale = :locale')
                ->innerJoin('qt.meta', 'qm')
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
            ->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->innerJoin('c.translations', 'ct', Join::WITH, 'ct.locale = :locale')
            ->innerJoin('ct.meta', 'm', Join::WITH, 'm.url = :url')
            ->setParameter('url', $url)
            ->setParameter('locale', $locale);

        if ($id !== null) {
            $query
                ->andWhere('c.id != :id')
                ->setParameter('id', $id);
        }

        if ((int) $query->getQuery()->getSingleScalarResult() === 0) {
            return $url;
        }

        return $this->getUrl(Model::addNumber($url), $locale, $id);
    }

    public function getNextSequence(): int
    {
        return $this
            ->createQueryBuilder('c')
            ->select('MAX(c.sequence) + 1')
            ->getQuery()
            ->getSingleScalarResult() ?? 1;
    }
}
