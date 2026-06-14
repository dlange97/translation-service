<?php

declare(strict_types=1);

namespace App\Service\Locale;

final class ConfiguredLocaleStrategy implements LocaleStrategyInterface
{
    /** @var list<string> */
    private array $supportedLocales;
    private string $defaultLocale;

    /**
     * @param array<int, string> $supportedLocales
     */
    public function __construct(array $supportedLocales = ['en', 'pl'], string $defaultLocale = 'en')
    {
        $normalized = [];
        foreach ($supportedLocales as $locale) {
            $locale = strtolower(trim($locale));
            if ($locale !== '' && !in_array($locale, $normalized, true)) {
                $normalized[] = $locale;
            }
        }

        if ($normalized === []) {
            $normalized = ['en', 'pl'];
        }

        $this->supportedLocales = $normalized;

        $normalizedDefault = strtolower(trim($defaultLocale));
        if ($normalizedDefault === '' || !in_array($normalizedDefault, $this->supportedLocales, true)) {
            $normalizedDefault = $this->supportedLocales[0];
        }

        $this->defaultLocale = $normalizedDefault;
    }

    public function supportedLocales(): array
    {
        return $this->supportedLocales;
    }

    public function defaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function normalizeRequestedLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));

        if ($locale === '' || !in_array($locale, $this->supportedLocales, true)) {
            return $this->defaultLocale;
        }

        return $locale;
    }
}
