<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TranslationController extends AbstractController
{
    public function __construct(
        private readonly TranslationService $translationService,
    ) {
    }

    #[Route('/translation/translations', name: 'translation_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $locale = $request->query->get('locale', 'en');

        return $this->json($this->translationService->getFlatMap(is_string($locale) ? $locale : 'en'));
    }

    #[Route('/translation/admin/translations', name: 'translation_admin_list', methods: ['GET'])]
    public function adminList(): JsonResponse
    {
        return $this->json($this->translationService->findAllGrouped());
    }

    #[Route('/translation/admin/translations', name: 'translation_admin_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        return $this->json($this->translationService->create($data), Response::HTTP_CREATED);
    }

    #[Route('/translation/admin/translations/{key}', name: 'translation_admin_update', methods: ['PUT'])]
    public function update(string $key, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        return $this->json($this->translationService->update($key, $data));
    }

    #[Route('/translation/admin/translations/{key}', name: 'translation_admin_delete', methods: ['DELETE'])]
    public function delete(string $key): JsonResponse
    {
        $this->translationService->delete($key);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
