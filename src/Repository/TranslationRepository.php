<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Translation;
use App\Entity\TranslationGroup;
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
            ->select('g.translationKey AS translationKey, t.translationValue')
            ->join('t.group', 'g')
            ->where('t.locale = :locale')
            ->setParameter('locale', $locale)
            ->orderBy('g.translationKey', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($results as $row) {
            $map[$row['translationKey']] = $row['translationValue'];
        }

        return $map;
    }

    /**
     * @return list<array{
     *     groupId: int,
     *     translationKey: string,
     *     values: array{en: string, pl: string},
     *     ids: array{en: int|null, pl: int|null},
     *     createdAt: string|null,
     *     updatedAt: string|null
     * }>
     */
    public function findAllGrouped(): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select(
                'g.id AS groupId',
                'g.translationKey AS translationKey',
                't.id AS translationId',
                't.locale AS locale',
                't.translationValue AS translationValue',
                't.createdAt AS createdAt',
                't.updatedAt AS updatedAt',
            )
            ->join('t.group', 'g')
            ->orderBy('g.translationKey', 'ASC')
            ->addOrderBy('t.locale', 'ASC')
            ->getQuery()
            ->getArrayResult();

        /** @var array<string, array{groupId: int, translationKey: string, values: array{en: string, pl: string}, ids: array{en: int|null, pl: int|null}, createdAt: string|null, updatedAt: string|null}> $grouped */
        $grouped = [];

        foreach ($rows as $row) {
            $key = (string) ($row['translationKey'] ?? '');
            if ($key === '') {
                continue;
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'groupId' => (int) $row['groupId'],
                    'translationKey' => $key,
                    'values' => ['en' => '', 'pl' => ''],
                    'ids' => ['en' => null, 'pl' => null],
                    'createdAt' => null,
                    'updatedAt' => null,
                ];
            }

            $locale = strtolower(trim((string) ($row['locale'] ?? '')));
            if (!isset($grouped[$key]['values'][$locale])) {
                continue;
            }

            $grouped[$key]['values'][$locale] = (string) ($row['translationValue'] ?? '');
            $grouped[$key]['ids'][$locale] = isset($row['translationId']) ? (int) $row['translationId'] : null;

            if ($grouped[$key]['createdAt'] === null && isset($row['createdAt'])) {
                $grouped[$key]['createdAt'] = $row['createdAt'] instanceof \DateTimeInterface
                    ? $row['createdAt']->format('c')
                    : (string) $row['createdAt'];
            }

            if (isset($row['updatedAt'])) {
                $grouped[$key]['updatedAt'] = $row['updatedAt'] instanceof \DateTimeInterface
                    ? $row['updatedAt']->format('c')
                    : (string) $row['updatedAt'];
            }
        }

        return array_values($grouped);
    }

    /** @return array{en: ?Translation, pl: ?Translation} */
    public function findGroupedByKey(string $key): array
    {
        $rows = $this->createQueryBuilder('t')
            ->join('t.group', 'g')
            ->where('g.translationKey = :key')
            ->setParameter('key', $key)
            ->orderBy('t.locale', 'ASC')
            ->getQuery()
            ->getResult();

        $group = ['en' => null, 'pl' => null];
        foreach ($rows as $row) {
            if (!$row instanceof Translation) {
                continue;
            }

            $locale = $row->getLocale();
            if (isset($group[$locale])) {
                $group[$locale] = $row;
            }
        }

        return $group;
    }

    public function hasAnyForKey(string $key): bool
    {
        $count = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->join('t.group', 'g')
            ->where('g.translationKey = :key')
            ->setParameter('key', $key)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function deleteByKey(string $key): int
    {
        $group = $this->findGroupByKey($key);
        if ($group === null) {
            return 0;
        }

        $this->getEntityManager()->remove($group);

        return 1;
    }

    public function findGroupByKey(string $key): ?TranslationGroup
    {
        return $this->getEntityManager()
            ->getRepository(TranslationGroup::class)
            ->findOneBy(['translationKey' => $key]);
    }

    public function findByLocaleAndKey(string $locale, string $key): ?Translation
    {
        return $this->createQueryBuilder('t')
            ->join('t.group', 'g')
            ->where('t.locale = :locale')
            ->andWhere('g.translationKey = :key')
            ->setParameter('locale', $locale)
            ->setParameter('key', $key)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
