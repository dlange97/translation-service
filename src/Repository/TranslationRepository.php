<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Translation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Translation>
 */
class TranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Translation::class);
    }

    public function save(Translation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Translation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<string, string>
     */
    public function getFlatMapByLocale(string $locale): array
    {
        $results = $this->createQueryBuilder('t')
            ->select('t.translationKey, t.translationValue')
            ->where('t.locale = :locale')
            ->setParameter('locale', $locale)
            ->orderBy('t.translationKey', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($results as $row) {
            $map[$row['translationKey']] = $row['translationValue'];
        }

        return $map;
    }

    /** @return list<Translation> */
    public function findAllOrdered(?string $locale = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->orderBy('t.locale', 'ASC')
            ->addOrderBy('t.translationKey', 'ASC');

        if ($locale !== null) {
            $qb->where('t.locale = :locale')->setParameter('locale', $locale);
        }

        /** @var list<Translation> */
        return $qb->getQuery()->getResult();
    }

    public function findByLocaleAndKey(string $locale, string $key): ?Translation
    {
        return $this->findOneBy(['locale' => $locale, 'translationKey' => $key]);
    }
}
