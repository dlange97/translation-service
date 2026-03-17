<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Translation;
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
    public function adminList(Request $request): JsonResponse
    {
        $locale = $request->query->get('locale');
        if ($locale !== null && !in_array($locale, Translation::SUPPORTED_LOCALES, true)) {
            $locale = null;
        }

        $items = $this->repo->findAllOrdered($locale);

        return $this->json(array_map(fn(Translation $t) => $this->serialize($t), $items));
    }

    #[Route('/translation/admin/translations', name: 'translation_admin_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $locale = trim((string) ($data['locale'] ?? 'en'));
        $key = trim((string) ($data['translationKey'] ?? ''));
        $value = (string) ($data['translationValue'] ?? '');

        if ($this->repo->findByLocaleAndKey($locale, $key) !== null) {
            return $this->json(
                ['error' => sprintf('Translation "%s" for locale "%s" already exists.', $key, $locale)],
                Response::HTTP_CONFLICT,
            );
        }

        $translation = (new Translation())
            ->setLocale($locale)
            ->setTranslationKey($key)
            ->setTranslationValue($value);

        $errors = $this->validator->validate($translation);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->repo->save($translation, true);

        return $this->json($this->serialize($translation), Response::HTTP_CREATED);
    }

    #[Route('/translation/admin/translations/{id}', name: 'translation_admin_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $translation = $this->repo->find($id);
        if ($translation === null) {
            return $this->json(['error' => 'Translation not found.'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['translationKey'])) {
            $newKey = trim((string) $data['translationKey']);
            $newLocale = isset($data['locale']) ? trim((string) $data['locale']) : $translation->getLocale();
            // Check uniqueness only if key or locale changed
            if ($newKey !== $translation->getTranslationKey() || $newLocale !== $translation->getLocale()) {
                $existing = $this->repo->findByLocaleAndKey($newLocale, $newKey);
                if ($existing !== null && $existing->getId() !== $id) {
                    return $this->json(
                        ['error' => sprintf('Translation "%s" for locale "%s" already exists.', $newKey, $newLocale)],
                        Response::HTTP_CONFLICT,
                    );
                }
            }
            $translation->setTranslationKey($newKey);
            if (isset($data['locale'])) {
                $translation->setLocale($newLocale);
            }
        }

        if (isset($data['translationValue'])) {
            $translation->setTranslationValue((string) $data['translationValue']);
        }

        $errors = $this->validator->validate($translation);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->flush();

        return $this->json($this->serialize($translation));
    }

    #[Route('/translation/admin/translations/{id}', name: 'translation_admin_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $translation = $this->repo->find($id);
        if ($translation === null) {
            return $this->json(['error' => 'Translation not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->repo->remove($translation, true);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /** @return array<string, mixed> */
    private function serialize(Translation $t): array
    {
        return [
            'id'               => $t->getId(),
            'locale'           => $t->getLocale(),
            'translationKey'   => $t->getTranslationKey(),
            'translationValue' => $t->getTranslationValue(),
            'createdAt'        => $t->getCreatedAt()?->format('c'),
            'updatedAt'        => $t->getUpdatedAt()?->format('c'),
        ];
    }
}
