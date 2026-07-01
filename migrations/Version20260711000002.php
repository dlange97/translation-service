<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add EN/PL translations for Wish Search and ensure add-user form keys are present';
    }

    public function up(Schema $schema): void
    {
        $now = date('Y-m-d H:i:s');

        $translations = [
            // Navigation + settings section for Wish Search
            ['en', 'nav.wishSearch', 'Wish Search'],
            ['pl', 'nav.wishSearch', 'Wyszukiwarka życzeń'],
            ['en', 'settings.wishSearch', '🪄 Wish Search (AI)'],
            ['pl', 'settings.wishSearch', '🪄 Wyszukiwarka życzeń (AI)'],
            ['en', 'settings.wishSearchSubtitle', 'Default topic, result limit and active AI provider'],
            ['pl', 'settings.wishSearchSubtitle', 'Domyślny temat, limit wyników i aktywny dostawca AI'],

            // Wish Search page
            ['en', 'wishSearch.title', 'Wish Search'],
            ['pl', 'wishSearch.title', 'Wyszukiwarka życzeń'],
            ['en', 'wishSearch.subtitle', 'Pick a topic, describe what you are looking for and let AI build a matching list.'],
            ['pl', 'wishSearch.subtitle', 'Wybierz temat, opisz czego szukasz i pozwól AI zbudować dopasowaną listę.'],
            ['en', 'wishSearch.provider', 'Provider'],
            ['pl', 'wishSearch.provider', 'Dostawca'],
            ['en', 'wishSearch.limit', 'Results'],
            ['pl', 'wishSearch.limit', 'Wyniki'],
            ['en', 'wishSearch.searching', 'Searching…'],
            ['pl', 'wishSearch.searching', 'Wyszukiwanie…'],
            ['en', 'wishSearch.search', 'Search'],
            ['pl', 'wishSearch.search', 'Szukaj'],
            ['en', 'wishSearch.resultsFor', 'Results'],
            ['pl', 'wishSearch.resultsFor', 'Wyniki'],
            ['en', 'wishSearch.noResults', 'No matching results found.'],
            ['pl', 'wishSearch.noResults', 'Nie znaleziono pasujących wyników.'],
            ['en', 'wishSearch.loadFailed', 'Failed to load topics.'],
            ['pl', 'wishSearch.loadFailed', 'Nie udało się załadować tematów.'],
            ['en', 'wishSearch.searchFailed', 'Search failed.'],
            ['pl', 'wishSearch.searchFailed', 'Wyszukiwanie nie powiodło się.'],

            // Wish Search settings
            ['en', 'wishSearch.settingsSaved', 'Preferences saved.'],
            ['pl', 'wishSearch.settingsSaved', 'Preferencje zapisane.'],
            ['en', 'wishSearch.defaultTopic', 'Default topic'],
            ['pl', 'wishSearch.defaultTopic', 'Domyślny temat'],
            ['en', 'wishSearch.defaultLimit', 'Default results'],
            ['pl', 'wishSearch.defaultLimit', 'Domyślna liczba wyników'],
            ['en', 'wishSearch.activeProvider', 'Active provider'],
            ['pl', 'wishSearch.activeProvider', 'Aktywny dostawca'],
            ['en', 'wishSearch.model', 'Model'],
            ['pl', 'wishSearch.model', 'Model'],
            ['en', 'wishSearch.providerDefault', 'provider default'],
            ['pl', 'wishSearch.providerDefault', 'domyślny dostawcy'],
            ['en', 'wishSearch.available', 'Available providers'],
            ['pl', 'wishSearch.available', 'Dostępni dostawcy'],
            ['en', 'wishSearch.keysHint', 'Provider selection and API keys are configured server-side via environment variables (AI_PROVIDER, ANTHROPIC_API_KEY, AWS_BEDROCK_*).'],
            ['pl', 'wishSearch.keysHint', 'Wybór dostawcy i klucze API są konfigurowane po stronie serwera przez zmienne środowiskowe (AI_PROVIDER, ANTHROPIC_API_KEY, AWS_BEDROCK_*).'],
            ['en', 'wishSearch.settingsLoadFailed', 'Failed to load.'],
            ['pl', 'wishSearch.settingsLoadFailed', 'Nie udało się załadować danych.'],
            ['en', 'wishSearch.configuredFallback', '(configured: {{configured}} - falling back)'],
            ['pl', 'wishSearch.configuredFallback', '(skonfigurowany: {{configured}} - użyto fallbacku)'],

            // Add-user form (reseed to guarantee EN/PL values)
            ['en', 'users.form.firstName', 'First name'],
            ['pl', 'users.form.firstName', 'Imię'],
            ['en', 'users.form.lastName', 'Last name'],
            ['pl', 'users.form.lastName', 'Nazwisko'],
            ['en', 'users.form.inviteToggle', 'Send a secure invitation link so the user sets their own password'],
            ['pl', 'users.form.inviteToggle', 'Wyślij bezpieczny link zaproszenia, aby użytkownik ustawił własne hasło'],
            ['en', 'users.form.inviteHint', 'When enabled, the user receives an invitation with a secure link to set their password and activate the account. No password is set here.'],
            ['pl', 'users.form.inviteHint', 'Po włączeniu użytkownik otrzyma zaproszenie z bezpiecznym linkiem do ustawienia hasła i aktywacji konta. Hasło nie jest ustawiane tutaj.'],
        ];

        foreach ($translations as [$locale, $key, $value]) {
            $this->addSql(sprintf(
                "INSERT INTO translation_group (translation_key, created_at, updated_at) VALUES ('%s', '%s', '%s') ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at)",
                $this->escape($key),
                $now,
                $now,
            ));

            $this->addSql(sprintf(
                "INSERT INTO translation (translation_group_id, locale, translation_value, created_at, updated_at) SELECT tg.id, '%s', '%s', '%s', '%s' FROM translation_group tg WHERE tg.translation_key = '%s' ON DUPLICATE KEY UPDATE translation_value = VALUES(translation_value), updated_at = VALUES(updated_at)",
                $this->escape($locale),
                $this->escape($value),
                $now,
                $now,
                $this->escape($key),
            ));
        }
    }

    public function down(Schema $schema): void
    {
        $keys = [
            'nav.wishSearch',
            'settings.wishSearch',
            'settings.wishSearchSubtitle',
            'wishSearch.title',
            'wishSearch.subtitle',
            'wishSearch.provider',
            'wishSearch.limit',
            'wishSearch.searching',
            'wishSearch.search',
            'wishSearch.resultsFor',
            'wishSearch.noResults',
            'wishSearch.loadFailed',
            'wishSearch.searchFailed',
            'wishSearch.settingsSaved',
            'wishSearch.defaultTopic',
            'wishSearch.defaultLimit',
            'wishSearch.activeProvider',
            'wishSearch.model',
            'wishSearch.providerDefault',
            'wishSearch.available',
            'wishSearch.keysHint',
            'wishSearch.settingsLoadFailed',
            'wishSearch.configuredFallback',
        ];

        foreach ($keys as $key) {
            $this->addSql(sprintf(
                "DELETE FROM translation WHERE translation_group_id IN (SELECT id FROM translation_group WHERE translation_key = '%s')",
                $this->escape($key),
            ));
            $this->addSql(sprintf(
                "DELETE FROM translation_group WHERE translation_key = '%s'",
                $this->escape($key),
            ));
        }
    }

    private function escape(string $value): string
    {
        return str_replace("'", "''", $value);
    }
}
