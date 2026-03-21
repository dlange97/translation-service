<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260321000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dashboard reset confirmation modal translation keys';
    }

    public function up(Schema $schema): void
    {
        $now = date('Y-m-d H:i:s');

        $translations = [
            ['en', 'dashboard.resetConfirmTitle', 'Reset tile settings?'],
            ['pl', 'dashboard.resetConfirmTitle', 'Zresetować ustawienia kafelków?'],
            ['en', 'dashboard.resetConfirmMessage', 'This will restore the default tile order and sizes.'],
            ['pl', 'dashboard.resetConfirmMessage', 'To przywróci domyślną kolejność i rozmiary kafelków.'],
            ['en', 'dashboard.resetConfirmAction', 'Reset'],
            ['pl', 'dashboard.resetConfirmAction', 'Resetuj'],
            ['en', 'dashboard.resetConfirmCancel', 'Cancel'],
            ['pl', 'dashboard.resetConfirmCancel', 'Anuluj'],
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
            'dashboard.resetConfirmTitle',
            'dashboard.resetConfirmMessage',
            'dashboard.resetConfirmAction',
            'dashboard.resetConfirmCancel',
        ];

        foreach ($keys as $key) {
            $escapedKey = str_replace("'", "\\'", $key);
            $this->addSql("DELETE FROM translation_group WHERE translation_key = '{$escapedKey}'");
        }
    }
}
