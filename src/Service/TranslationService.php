<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Translation;
use App\Entity\TranslationGroup;
use App\Repository\TranslationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TranslationService
{
    public function __construct(
        private readonly TranslationRepository $repo,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /** @return array<string, string> */
    public function getFlatMap(string $locale): array
    {
        if (!in_array($locale, Translation::SUPPORTED_LOCALES, true)) {
            $locale = 'en';
        }

        return $this->repo->getFlatMapByLocale($locale);
    }

    /** @return list<array<string, mixed>> */
    public function findAllGrouped(): array
    {
        return $this->repo->findAllGrouped();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws UnprocessableEntityHttpException|ConflictHttpException
     */
    public function create(array $data): array
    {
        $key    = trim((string) ($data['translationKey'] ?? ''));
        $values = $this->extractValues($data);

        if ($key === '') {
            throw new UnprocessableEntityHttpException('Translation key is required.');
        }

        if ($this->repo->hasAnyForKey($key)) {
            throw new ConflictHttpException(sprintf('Translation "%s" already exists.', $key));
        }

        if ($values['en'] === '' || $values['pl'] === '') {
            throw new UnprocessableEntityHttpException('Both translation values (en, pl) are required.');
        }

        $group = (new TranslationGroup())->setTranslationKey($key);

        $en = (new Translation())
            ->setLocale('en')
            ->setGroup($group)
            ->setTranslationValue($values['en']);

        $pl = (new Translation())
            ->setLocale('pl')
            ->setGroup($group)
            ->setTranslationValue($values['pl']);

        $this->validateEntities($group, $en, $pl);

        $this->em->persist($group);
        $this->repo->save($en);
        $this->repo->save($pl, true);

        return $this->serializeGrouped($key, $en, $pl);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws NotFoundHttpException|UnprocessableEntityHttpException
     */
    public function update(string $key, array $data): array
    {
        $decodedKey = trim(rawurldecode($key));
        if ($decodedKey === '') {
            throw new UnprocessableEntityHttpException('Translation key is required.');
        }

        $en = $this->repo->findByLocaleAndKey('en', $decodedKey);
        $pl = $this->repo->findByLocaleAndKey('pl', $decodedKey);
        if ($en === null && $pl === null) {
            throw new NotFoundHttpException('Translation not found.');
        }

        $values = $this->extractValues($data);

        $translationGroup = $en?->getGroup() ?? $pl?->getGroup();
        if ($translationGroup === null) {
            throw new NotFoundHttpException('Translation group not found.');
        }

        if ($en === null) {
            $en = (new Translation())
                ->setLocale('en')
                ->setGroup($translationGroup);
        }

        if ($pl === null) {
            $pl = (new Translation())
                ->setLocale('pl')
                ->setGroup($translationGroup);
        }

        if ($values['en'] !== '') {
            $en->setTranslationValue($values['en']);
        }

        if ($values['pl'] !== '') {
            $pl->setTranslationValue($values['pl']);
        }

        if ($en->getTranslationValue() === '' || $pl->getTranslationValue() === '') {
            throw new UnprocessableEntityHttpException('Both translation values (en, pl) are required.');
        }

        $this->validateEntities($translationGroup, $en, $pl);

        $this->repo->save($en);
        $this->repo->save($pl, true);

        return $this->serializeGrouped($decodedKey, $en, $pl);
    }

    /**
     * @throws NotFoundHttpException|UnprocessableEntityHttpException
     */
    public function delete(string $key): void
    {
        $decodedKey = trim(rawurldecode($key));
        if ($decodedKey === '') {
            throw new UnprocessableEntityHttpException('Translation key is required.');
        }

        $deleted = $this->repo->deleteByKey($decodedKey);
        if ($deleted === 0) {
            throw new NotFoundHttpException('Translation not found.');
        }

        $this->em->flush();
    }

    /**
     * @param array<string, mixed> $data
     * @return array{en: string, pl: string}
     */
    public function extractValues(array $data): array
    {
        $rawValues = $data['values'] ?? [];
        $en = trim((string) ($rawValues['en'] ?? $data['translationValueEn'] ?? ''));
        $pl = trim((string) ($rawValues['pl'] ?? $data['translationValuePl'] ?? ''));

        return ['en' => $en, 'pl' => $pl];
    }

    /** @return array<string, mixed> */
    public function serializeGrouped(string $key, ?Translation $en, ?Translation $pl): array
    {
        return [
            'groupId'        => $en?->getGroup()?->getId() ?? $pl?->getGroup()?->getId(),
            'translationKey' => $key,
            'values'         => [
                'en' => $en?->getTranslationValue() ?? '',
                'pl' => $pl?->getTranslationValue() ?? '',
            ],
            'ids' => [
                'en' => $en?->getId(),
                'pl' => $pl?->getId(),
            ],
            'createdAt' => $en?->getCreatedAt()?->format('c') ?? $pl?->getCreatedAt()?->format('c'),
            'updatedAt' => $en?->getUpdatedAt()?->format('c') ?? $pl?->getUpdatedAt()?->format('c'),
        ];
    }

    private function validateEntities(TranslationGroup $group, Translation $en, Translation $pl): void
    {
        $errors = [
            ...iterator_to_array($this->validator->validate($group)),
            ...iterator_to_array($this->validator->validate($en)),
            ...iterator_to_array($this->validator->validate($pl)),
        ];

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new UnprocessableEntityHttpException(implode(' | ', $messages));
        }
    }
}
