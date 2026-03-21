<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Translation;
use App\Entity\TranslationGroup;
use App\Repository\TranslationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TranslationController extends AbstractController
{
    public function __construct(
        private readonly TranslationRepository $repo,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Returns all translations for a given locale as a flat key→value map.
     * Requires valid JWT (ROLE_USER). Frontend uses this to populate i18n.
     */
    #[Route('/translation/translations', name: 'translation_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $locale = $request->query->get('locale', 'en');
        if (!in_array($locale, Translation::SUPPORTED_LOCALES, true)) {
            $locale = 'en';
        }

        return $this->json($this->repo->getFlatMapByLocale($locale));
    }

    // ── Admin endpoints ──────────────────────────────────────────────────────
    // Access guarded by security.yaml allow_if: 'settings.view' in permissions

    /**
     * Returns all translations (full list with IDs) for admin management.
     */
    #[Route('/translation/admin/translations', name: 'translation_admin_list', methods: ['GET'])]
    public function adminList(): JsonResponse
    {
        return $this->json($this->repo->findAllGrouped());
    }

    #[Route('/translation/admin/translations', name: 'translation_admin_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $key = trim((string) ($data['translationKey'] ?? ''));
        $values = $this->extractValues($data);

        if ($key === '') {
            return $this->json(['error' => 'Translation key is required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->repo->hasAnyForKey($key)) {
            return $this->json(
                ['error' => sprintf('Translation "%s" already exists.', $key)],
                Response::HTTP_CONFLICT,
            );
        }

        if ($values['en'] === '' || $values['pl'] === '') {
            return $this->json(
                ['error' => 'Both translation values (en, pl) are required.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $group = (new TranslationGroup())
            ->setTranslationKey($key);

        $en = (new Translation())
            ->setLocale('en')
            ->setGroup($group)
            ->setTranslationValue($values['en']);

        $pl = (new Translation())
            ->setLocale('pl')
            ->setGroup($group)
            ->setTranslationValue($values['pl']);

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
            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->persist($group);
        $this->repo->save($en);
        $this->repo->save($pl, true);

        return $this->json($this->serializeGrouped($key, $en, $pl), Response::HTTP_CREATED);
    }

    #[Route('/translation/admin/translations/{key}', name: 'translation_admin_update', methods: ['PUT'])]
    public function update(string $key, Request $request): JsonResponse
    {
        $decodedKey = trim(rawurldecode($key));
        if ($decodedKey === '') {
            return $this->json(['error' => 'Translation key is required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $en = $this->repo->findByLocaleAndKey('en', $decodedKey);
        $pl = $this->repo->findByLocaleAndKey('pl', $decodedKey);
        if ($en === null && $pl === null) {
            return $this->json(['error' => 'Translation not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $values = $this->extractValues($data);

        $translationGroup = $en?->getGroup() ?? $pl?->getGroup();
        if ($translationGroup === null) {
            return $this->json(['error' => 'Translation group not found.'], Response::HTTP_NOT_FOUND);
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
            return $this->json(
                ['error' => 'Both translation values (en, pl) are required.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $errors = [
            ...iterator_to_array($this->validator->validate($translationGroup)),
            ...iterator_to_array($this->validator->validate($en)),
            ...iterator_to_array($this->validator->validate($pl)),
        ];
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->repo->save($en);
        $this->repo->save($pl, true);

        return $this->json($this->serializeGrouped($decodedKey, $en, $pl));
    }

    #[Route('/translation/admin/translations/{key}', name: 'translation_admin_delete', methods: ['DELETE'])]
    public function delete(string $key): JsonResponse
    {
        $decodedKey = trim(rawurldecode($key));
        if ($decodedKey === '') {
            return $this->json(['error' => 'Translation key is required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $deleted = $this->repo->deleteByKey($decodedKey);
        if ($deleted === 0) {
            return $this->json(['error' => 'Translation not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param array<string, mixed> $data
     * @return array{en: string, pl: string}
     */
    private function extractValues(array $data): array
    {
        $rawValues = $data['values'] ?? [];
        $en = trim((string) ($rawValues['en'] ?? $data['translationValueEn'] ?? ''));
        $pl = trim((string) ($rawValues['pl'] ?? $data['translationValuePl'] ?? ''));

        return ['en' => $en, 'pl' => $pl];
    }

    /** @return array<string, mixed> */
    private function serializeGrouped(string $key, ?Translation $en, ?Translation $pl): array
    {
        return [
            'groupId' => $en?->getGroup()?->getId() ?? $pl?->getGroup()?->getId(),
            'translationKey' => $key,
            'values' => [
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
}
