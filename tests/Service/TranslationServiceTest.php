<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Translation;
use App\Entity\TranslationGroup;
use App\Repository\TranslationRepository;
use App\Service\Locale\LocaleStrategyInterface;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TranslationServiceTest extends TestCase
{
    private TranslationRepository&MockObject $repo;
    private EntityManagerInterface&MockObject $em;
    private ValidatorInterface&MockObject $validator;
    private LocaleStrategyInterface&MockObject $localeStrategy;
    private TranslationService $service;
    /** @var list<string> */
    private array $supportedLocales = ['en', 'pl'];

    protected function setUp(): void
    {
        $this->repo = $this->createMock(TranslationRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->localeStrategy = $this->createMock(LocaleStrategyInterface::class);

        $this->localeStrategy->method('supportedLocales')->willReturnCallback(fn(): array => $this->supportedLocales);
        $this->localeStrategy->method('defaultLocale')->willReturnCallback(fn(): string => $this->supportedLocales[0]);
        $this->localeStrategy->method('normalizeRequestedLocale')->willReturnCallback(
            fn(string $locale): string => in_array($locale, $this->supportedLocales, true) ? $locale : $this->supportedLocales[0],
        );

        $this->service = new TranslationService($this->repo, $this->em, $this->validator, $this->localeStrategy);
    }

    // --- getFlatMap ---

    public function testGetFlatMapDelegatesToRepository(): void
    {
        $this->repo->method('getFlatMapByLocale')
            ->with('pl')
            ->willReturn(['app.hello' => 'Cześć']);

        $result = $this->service->getFlatMap('pl');
        $this->assertSame(['app.hello' => 'Cześć'], $result);
    }

    public function testGetFlatMapDefaultsToEnForUnsupportedLocale(): void
    {
        $this->localeStrategy->expects($this->once())
            ->method('normalizeRequestedLocale')
            ->with('de')
            ->willReturn('en');

        $this->repo->expects($this->once())
            ->method('getFlatMapByLocale')
            ->with('en')
            ->willReturn([]);

        $this->service->getFlatMap('de');
    }

    // --- findAllGrouped ---

    public function testFindAllGroupedDelegatesToRepository(): void
    {
        $this->repo->method('findAllGrouped')->with(['en', 'pl'])->willReturn([['key' => 'app.test']]);

        $result = $this->service->findAllGrouped();
        $this->assertCount(1, $result);
    }

    // --- create ---

    public function testCreateThrowsOnEmptyKey(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('Translation key is required.');

        $this->service->create(['translationKey' => '', 'values' => ['en' => 'a', 'pl' => 'b']]);
    }

    public function testCreateThrowsOnDuplicateKey(): void
    {
        $this->repo->method('hasAnyForKey')->willReturn(true);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('already exists');

        $this->service->create([
            'translationKey' => 'app.dup',
            'values' => ['en' => 'Hello', 'pl' => 'Cześć'],
        ]);
    }

    public function testCreateThrowsWhenAnyRequiredLocaleIsEmpty(): void
    {
        $this->repo->method('hasAnyForKey')->willReturn(false);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('locales: en, pl');

        $this->service->create([
            'translationKey' => 'app.new',
            'values' => ['en' => '', 'pl' => ''],
        ]);
    }

    public function testCreatePersistsAndReturnsGroupedData(): void
    {
        $this->repo->method('hasAnyForKey')->willReturn(false);
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());

        $this->em->expects($this->once())->method('persist');
        $this->repo->expects($this->exactly(2))->method('save');

        $result = $this->service->create([
            'translationKey' => 'app.hello',
            'values' => ['en' => 'Hello', 'pl' => 'Cześć'],
        ]);

        $this->assertSame('app.hello', $result['translationKey']);
        $this->assertSame('Hello', $result['values']['en']);
        $this->assertSame('Cześć', $result['values']['pl']);
    }

    // --- update ---

    public function testUpdateThrowsOnEmptyKey(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);

        $this->service->update('', ['values' => ['en' => 'Hi']]);
    }

    public function testUpdateThrowsNotFoundWhenNoTranslations(): void
    {
        $this->repo->method('findByKeyIndexedByLocale')->willReturn([]);

        $this->expectException(NotFoundHttpException::class);

        $this->service->update('app.missing', ['values' => ['en' => 'Hi']]);
    }

    public function testUpdateModifiesExistingTranslation(): void
    {
        $group = (new TranslationGroup())->setTranslationKey('app.hello');
        $en = (new Translation())->setLocale('en')->setGroup($group)->setTranslationValue('Old');
        $pl = (new Translation())->setLocale('pl')->setGroup($group)->setTranslationValue('Stare');

        $this->repo->method('findByKeyIndexedByLocale')
            ->willReturn(['en' => $en, 'pl' => $pl]);

        $this->validator->method('validate')->willReturn(new ConstraintViolationList());
        $this->repo->expects($this->exactly(2))->method('save');

        $result = $this->service->update('app.hello', [
            'values' => ['en' => 'New', 'pl' => 'Nowe'],
        ]);

        $this->assertSame('New', $result['values']['en']);
        $this->assertSame('Nowe', $result['values']['pl']);
    }

    public function testUpdateDecodesUrlEncodedKey(): void
    {
        $group = (new TranslationGroup())->setTranslationKey('app.hello world');
        $en = (new Translation())->setLocale('en')->setGroup($group)->setTranslationValue('Hi');
        $pl = (new Translation())->setLocale('pl')->setGroup($group)->setTranslationValue('Cześć');

        $this->repo->expects($this->once())
            ->method('findByKeyIndexedByLocale')
            ->with('app.hello world')
            ->willReturn(['en' => $en, 'pl' => $pl]);

        $this->validator->method('validate')->willReturn(new ConstraintViolationList());

        $result = $this->service->update('app.hello%20world', [
            'values' => ['en' => 'Hi', 'pl' => 'Cześć'],
        ]);

        $this->assertSame('app.hello world', $result['translationKey']);
    }

    // --- delete ---

    public function testDeleteThrowsOnEmptyKey(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);

        $this->service->delete('');
    }

    public function testDeleteThrowsNotFoundWhenNoRowsDeleted(): void
    {
        $this->repo->method('deleteByKey')->willReturn(0);

        $this->expectException(NotFoundHttpException::class);

        $this->service->delete('app.missing');
    }

    public function testDeleteFlushesOnSuccess(): void
    {
        $this->repo->method('deleteByKey')->willReturn(2);
        $this->em->expects($this->once())->method('flush');

        $this->service->delete('app.hello');
    }

    // --- extractValues ---

    public function testExtractValuesFromValuesKey(): void
    {
        $result = $this->service->extractValues([
            'values' => ['en' => 'Hello', 'pl' => 'Cześć'],
        ]);

        $this->assertSame(['en' => 'Hello', 'pl' => 'Cześć'], $result);
    }

    public function testExtractValuesFallsBackToLegacyKeys(): void
    {
        $result = $this->service->extractValues([
            'translationValueEn' => 'Hello',
            'translationValuePl' => 'Cześć',
        ]);

        $this->assertSame(['en' => 'Hello', 'pl' => 'Cześć'], $result);
    }

    public function testExtractValuesSupportsAdditionalLocalesFromStrategy(): void
    {
        $this->supportedLocales = ['en', 'pl', 'de'];

        $result = $this->service->extractValues([
            'values' => ['en' => 'Hello', 'pl' => 'Cześć', 'de' => 'Hallo'],
        ]);

        $this->assertSame(['en' => 'Hello', 'pl' => 'Cześć', 'de' => 'Hallo'], $result);
    }

    public function testExtractValuesReturnsEmptyStringsWhenMissing(): void
    {
        $result = $this->service->extractValues([]);

        $this->assertSame(['en' => '', 'pl' => ''], $result);
    }

    // --- serializeGrouped ---

    public function testSerializeGroupedReturnsCorrectStructure(): void
    {
        $group = (new TranslationGroup())->setTranslationKey('app.test');
        $en = (new Translation())->setLocale('en')->setGroup($group)->setTranslationValue('Test');
        $pl = (new Translation())->setLocale('pl')->setGroup($group)->setTranslationValue('Test PL');

        $result = $this->service->serializeGrouped('app.test', ['en' => $en, 'pl' => $pl]);

        $this->assertSame('app.test', $result['translationKey']);
        $this->assertSame('Test', $result['values']['en']);
        $this->assertSame('Test PL', $result['values']['pl']);
        $this->assertArrayHasKey('groupId', $result);
        $this->assertArrayHasKey('ids', $result);
        $this->assertArrayHasKey('createdAt', $result);
        $this->assertArrayHasKey('updatedAt', $result);
    }

    public function testSerializeGroupedHandlesNullTranslations(): void
    {
        $result = $this->service->serializeGrouped('app.test', []);

        $this->assertSame('', $result['values']['en']);
        $this->assertSame('', $result['values']['pl']);
        $this->assertNull($result['groupId']);
    }

    public function testCreateWithAdditionalLocaleRequiresAndPersistsAllSupportedLocales(): void
    {
        $this->supportedLocales = ['en', 'pl', 'de'];
        $this->repo->method('hasAnyForKey')->willReturn(false);
        $this->validator->method('validate')->willReturn(new ConstraintViolationList());

        $this->repo->expects($this->exactly(3))->method('save');

        $result = $this->service->create([
            'translationKey' => 'app.welcome',
            'values' => ['en' => 'Welcome', 'pl' => 'Witaj', 'de' => 'Willkommen'],
        ]);

        $this->assertSame('Welcome', $result['values']['en']);
        $this->assertSame('Witaj', $result['values']['pl']);
        $this->assertSame('Willkommen', $result['values']['de']);
    }
}
