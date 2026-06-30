<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add translation keys for user invite option in add-user form';
    }

    public function up(Schema $schema): void
    {
        $now = date('Y-m-d H:i:s');

        $translations = [
            ['en', 'users.form.title', 'Create New User'],
            ['pl', 'users.form.title', 'Utwórz nowego użytkownika'],
            ['en', 'users.form.subtitle', 'Add a new application account only when you need it, then return to the selected profile view.'],
            ['pl', 'users.form.subtitle', 'Dodaj nowe konto aplikacyjne tylko wtedy, gdy jest potrzebne, a następnie wróć do wybranego widoku profilu.'],
            ['en', 'users.form.email', 'Email'],
            ['pl', 'users.form.email', 'Email'],
            ['en', 'users.form.emailPlaceholder', 'new.user@example.com'],
            ['pl', 'users.form.emailPlaceholder', 'nowy.uzytkownik@example.com'],
            ['en', 'users.form.password', 'Password'],
            ['pl', 'users.form.password', 'Hasło'],
            ['en', 'users.form.passwordPlaceholder', 'Min. 8 characters'],
            ['pl', 'users.form.passwordPlaceholder', 'Min. 8 znaków'],
            ['en', 'users.form.confirmPassword', 'Confirm password'],
            ['pl', 'users.form.confirmPassword', 'Potwierdź hasło'],
            ['en', 'users.form.confirmPasswordPlaceholder', 'Repeat password'],
            ['pl', 'users.form.confirmPasswordPlaceholder', 'Powtórz hasło'],
            ['en', 'users.form.role', 'Role'],
            ['pl', 'users.form.role', 'Rola'],
            ['en', 'users.form.firstNamePlaceholder', 'Jan'],
            ['pl', 'users.form.firstNamePlaceholder', 'Jan'],
            ['en', 'users.form.lastNamePlaceholder', 'Kowalski'],
            ['pl', 'users.form.lastNamePlaceholder', 'Kowalski'],
            ['en', 'users.form.inviteToggle', 'Send invitation email to this user after creation'],
            ['pl', 'users.form.inviteToggle', 'Wyślij e-mail z zaproszeniem do tego użytkownika po utworzeniu konta'],
            ['en', 'users.form.inviteHint', 'When enabled, the user receives an invitation notification (email/push depends on notification settings).'],
            ['pl', 'users.form.inviteHint', 'Po włączeniu użytkownik otrzyma powiadomienie zapraszające (e-mail/push zależy od ustawień notyfikacji).'],
            ['en', 'users.form.creating', 'Creating user…'],
            ['pl', 'users.form.creating', 'Tworzenie użytkownika…'],
            ['en', 'users.form.submit', 'Create user'],
            ['pl', 'users.form.submit', 'Utwórz użytkownika'],
            ['en', 'users.form.success', 'User {{email}} created successfully.'],
            ['pl', 'users.form.success', 'Użytkownik {{email}} został utworzony pomyślnie.'],
            ['en', 'users.form.error.create', 'Failed to create user.'],
            ['pl', 'users.form.error.create', 'Nie udało się utworzyć użytkownika.'],
            ['en', 'users.form.error.passwordMismatch', 'Passwords do not match.'],
            ['pl', 'users.form.error.passwordMismatch', 'Hasła nie są takie same.'],
            ['en', 'users.form.error.passwordLength', 'Password must be at least 8 characters.'],
            ['pl', 'users.form.error.passwordLength', 'Hasło musi mieć co najmniej 8 znaków.'],
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
            'users.form.title',
            'users.form.subtitle',
            'users.form.email',
            'users.form.emailPlaceholder',
            'users.form.password',
            'users.form.passwordPlaceholder',
            'users.form.confirmPassword',
            'users.form.confirmPasswordPlaceholder',
            'users.form.role',
            'users.form.firstNamePlaceholder',
            'users.form.lastNamePlaceholder',
            'users.form.inviteToggle',
            'users.form.inviteHint',
            'users.form.creating',
            'users.form.submit',
            'users.form.success',
            'users.form.error.create',
            'users.form.error.passwordMismatch',
            'users.form.error.passwordLength',
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
