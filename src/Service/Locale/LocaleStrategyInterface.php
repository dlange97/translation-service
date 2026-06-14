<?php

declare(strict_types=1);

namespace App\Service\Locale;

interface LocaleStrategyInterface
{
    /** @return list<string> */
    public function supportedLocales(): array;

    public function defaultLocale(): string;

    public function normalizeRequestedLocale(string $locale): string;
}
