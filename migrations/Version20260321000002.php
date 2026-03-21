<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260321000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing dashboard resize and event form translation keys';
    }

    public function up(Schema $schema): void
    {
        $now = date('Y-m-d H:i:s');

        $translations = [
            ['en', 'dashboard.resizeHint', 'Drag to resize'],
            ['pl', 'dashboard.resizeHint', 'Przeciągnij, aby zmienić rozmiar'],
            ['en', 'events.saveError', 'Failed to save event'],
            ['pl', 'events.saveError', 'Nie udało się zapisać wydarzenia'],
            ['en', 'events.deleteConfirm', 'Delete event'],
            ['pl', 'events.deleteConfirm', 'Usuń wydarzenie'],
            ['en', 'events.deleteError', 'Failed to delete event'],
            ['pl', 'events.deleteError', 'Nie udało się usunąć wydarzenia'],
            ['en', 'events.form.createTitle', 'New Event'],
            ['pl', 'events.form.createTitle', 'Nowe wydarzenie'],
            ['en', 'events.form.editTitle', 'Edit Event'],
            ['pl', 'events.form.editTitle', 'Edytuj wydarzenie'],
            ['en', 'events.form.titleLabel', 'Title *'],
            ['pl', 'events.form.titleLabel', 'Tytuł *'],
            ['en', 'events.form.titlePlaceholder', 'Event title'],
            ['pl', 'events.form.titlePlaceholder', 'Tytuł wydarzenia'],
            ['en', 'events.form.descriptionLabel', 'Description'],
            ['pl', 'events.form.descriptionLabel', 'Opis'],
            ['en', 'events.form.descriptionPlaceholder', 'Optional details…'],
            ['pl', 'events.form.descriptionPlaceholder', 'Opcjonalne szczegóły…'],
            ['en', 'events.form.startDateLabel', 'Start Date *'],
            ['pl', 'events.form.startDateLabel', 'Data rozpoczęcia *'],
            ['en', 'events.form.startTimeLabel', 'Start Time'],
            ['pl', 'events.form.startTimeLabel', 'Godzina rozpoczęcia'],
            ['en', 'events.form.endDateLabel', 'End Date'],
            ['pl', 'events.form.endDateLabel', 'Data zakończenia'],
            ['en', 'events.form.endTimeLabel', 'End Time'],
            ['pl', 'events.form.endTimeLabel', 'Godzina zakończenia'],
            ['en', 'events.form.locationLabel', 'Location (Poland)'],
            ['pl', 'events.form.locationLabel', 'Lokalizacja (Polska)'],
            ['en', 'events.form.saveChanges', 'Save Changes'],
            ['pl', 'events.form.saveChanges', 'Zapisz zmiany'],
        ];

        foreach ($translations as [$locale, $key, $value]) {
            $escapedKey = str_replace("'", "\\'", $key);
            $escapedValue = str_replace("'", "\\'", $value);

            $this->addSql(
                "INSERT IGNORE INTO translation_group (translation_key, created_at, updated_at) VALUES ('{$escapedKey}', '{$now}', '{$now}')"
            );

            $this->addSql(
                "INSERT IGNORE INTO translation (translation_group_id, locale, translation_value, created_at, updated_at) "
                . "SELECT id, '{$locale}', '{$escapedValue}', '{$now}', '{$now}' "
                . "FROM translation_group WHERE translation_key = '{$escapedKey}'"
            );
        }
    }

    public function down(Schema $schema): void
    {
        $keys = [
            'dashboard.resizeHint',
            'events.saveError',
            'events.deleteConfirm',
            'events.deleteError',
            'events.form.createTitle',
            'events.form.editTitle',
            'events.form.titleLabel',
            'events.form.titlePlaceholder',
            'events.form.descriptionLabel',
            'events.form.descriptionPlaceholder',
            'events.form.startDateLabel',
            'events.form.startTimeLabel',
            'events.form.endDateLabel',
            'events.form.endTimeLabel',
            'events.form.locationLabel',
            'events.form.saveChanges',
        ];

        foreach ($keys as $key) {
            $escapedKey = str_replace("'", "\\'", $key);
            $this->addSql("DELETE FROM translation_group WHERE translation_key = '{$escapedKey}'");
        }
    }
}