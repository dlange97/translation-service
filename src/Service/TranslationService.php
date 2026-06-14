<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Translation;
use App\Entity\TranslationGroup;
use App\Repository\TranslationRepository;
use App\Service\Locale\LocaleStrategyInterface;
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
        private readonly LocaleStrategyInterface $localeStrategy,
    ) {
    }

    /** @return array<string, string> */
    public function getFlatMap(string $locale): array
    {
        return $this->repo->getFlatMapByLocale(
            $this->localeStrategy->normalizeRequestedLocale($locale),
        );
    }

    /** @return list<array<string, mixed>> */
    public function findAllGrouped(): array
    {
        return $this->repo->findAllGrouped($this->localeStrategy->supportedLocales());
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws UnprocessableEntityHttpException|ConflictHttpException
     */
    public function create(array $data): array
    {
        $key = trim((string) ($data['translationKey'] ?? ''));
        $values = $this->extractValues($data);
        $locales = $this->localeStrategy->supportedLocales();

        if ($key === '') {
            throw new UnprocessableEntityHttpException('Translation key is required.');
        }

        if ($this->repo->hasAnyForKey($key)) {
            throw new ConflictHttpException(sprintf('Translation "%s" already exists.', $key));
        }

        $missingLocales = array_values(array_filter(
            $locales,
            static fn(string $locale): bool => ($values[$locale] ?? '') === '',
        ));
        if ($missingLocales !== []) {
            throw new UnprocessableEntityHttpException(sprintf(
                'Translation values are required for locales: %s.',
                implode(', ', $missingLocales),
            ));
        }

        $group = (new TranslationGroup())->setTranslationKey($key);
        $translationsByLocale = [];

        foreach ($locales as $locale) {
            $translationsByLocale[$locale] = (new Translation())
                ->setLocale($locale)
                ->setGroup($group)
                ->setTranslationValue($values[$locale]);
        }

        $this->validateEntities($group, array_values($translationsByLocale));

        $this->em->persist($group);

        $total = count($translationsByLocale);
        $index = 0;
        foreach ($translationsByLocale as $translation) {
            ++$index;
            $this->repo->save($translation, $index === $total);
        }

        return $this->serializeGrouped($key, $translationsByLocale);
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

        $existingByLocale = $this->repo->findByKeyIndexedByLocale($decodedKey);
        if ($existingByLocale === []) {
            throw new NotFoundHttpException('Translation not found.');
        }

        $values = $this->extractValues($data);
        $locales = $this->localeStrategy->supportedLocales();

        $sampleTranslation = reset($existingByLocale);
        $translationGroup = $sampleTranslation instanceof Translation ? $sampleTranslation->getGroup() : null;
        if ($translationGroup === null) {
            throw new NotFoundHttpException('Translation group not found.');
        }

        $translationsByLocale = [];
        $missingLocales = [];
        foreach ($locales as $locale) {
            $translation = $existingByLocale[$locale] ?? (new Translation())
                ->setLocale($locale)
                ->setGroup($translationGroup);

            if (($values[$locale] ?? '') !== '') {
                $translation->setTranslationValue($values[$locale]);
            }

            if ($translation->getTranslationValue() === '') {
                $missingLocales[] = $locale;
            }

            $translationsByLocale[$locale] = $translation;
        }

        if ($missingLocales !== []) {
            throw new UnprocessableEntityHttpException(sprintf(
                'Translation values are required for locales: %s.',
                implode(', ', $missingLocales),
            ));
        }

        $this->validateEntities($translationGroup, array_values($translationsByLocale));

        $total = count($translationsByLocale);
        $index = 0;
        foreach ($translationsByLocale as $translation) {
            ++$index;
            $this->repo->save($translation, $index === $total);
        }

        return $this->serializeGrouped($decodedKey, $translationsByLocale);
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
     * @return array<string, string>
     */
    public function extractValues(array $data): array
    {
        $locales = $this->localeStrategy->supportedLocales();
        $rawValues = is_array($data['values'] ?? null) ? $data['values'] : [];
        $values = [];

        foreach ($locales as $locale) {
            $legacyKey = 'translationValue' . $this->legacyLocaleSuffix($locale);
            $values[$locale] = trim((string) ($rawValues[$locale] ?? $data[$legacyKey] ?? ''));
        }

        return $values;
    }

    /**
     * @param array<string, Translation|null> $translationsByLocale
     * @return array<string, mixed>
     */
    public function serializeGrouped(string $key, array $translationsByLocale): array
    {
        $locales = $this->localeStrategy->supportedLocales();
        $values = array_fill_keys($locales, '');
        $ids = array_fill_keys($locales, null);
        $groupId = null;
        $createdAt = null;
        $updatedAt = null;

        foreach ($translationsByLocale as $locale => $translation) {
            if (!$translation instanceof Translation) {
                continue;
            }

            if (!array_key_exists($locale, $values)) {
                $values[$locale] = '';
                $ids[$locale] = null;
            }

            $groupId ??= $translation->getGroup()?->getId();
            $values[$locale] = $translation->getTranslationValue();
            $ids[$locale] = $translation->getId();
            $createdAt ??= $translation->getCreatedAt()?->format('c');
            $updatedAt = $translation->getUpdatedAt()?->format('c') ?? $updatedAt;
        }

        return [
            'groupId'        => $groupId,
            'translationKey' => $key,
            'values'         => $values,
            'ids' => $ids,
            'createdAt' => $createdAt,
            'updatedAt' => $updatedAt,
        ];
    }

    /**
     * @param list<Translation> $translations
     */
    private function validateEntities(TranslationGroup $group, array $translations): void
    {
        $errors = [...iterator_to_array($this->validator->validate($group))];

        foreach ($translations as $translation) {
            $errors = [
                ...$errors,
                ...iterator_to_array($this->validator->validate($translation)),
            ];
        }

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new UnprocessableEntityHttpException(implode(' | ', $messages));
        }
    }

    private function legacyLocaleSuffix(string $locale): string
    {
        $parts = preg_split('/[^a-z0-9]+/i', strtolower($locale)) ?: [];
        $suffix = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $suffix .= ucfirst($part);
        }

        return $suffix;
    }
}
