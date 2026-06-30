<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260710000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add translation keys for add-user name labels and set-password (invite) page';
    }

    public function up(Schema $schema): void
    {
        $now = date('Y-m-d H:i:s');

        $translations = [
            // Missing add-user form labels
            ['en', 'users.form.firstName', 'First name'],
            ['pl', 'users.form.firstName', 'Imię'],
            ['en', 'users.form.lastName', 'Last name'],
            ['pl', 'users.form.lastName', 'Nazwisko'],
            // Updated invite copy (link-based flow)
            ['en', 'users.form.inviteToggle', 'Send a secure invitation link so the user sets their own password'],
            ['pl', 'users.form.inviteToggle', 'Wyślij bezpieczny link zaproszenia, aby użytkownik ustawił własne hasło'],
            ['en', 'users.form.inviteHint', 'When enabled, the user receives an invitation with a secure link to set their password and activate the account. No password is set here.'],
            ['pl', 'users.form.inviteHint', 'Po włączeniu użytkownik otrzyma zaproszenie z bezpiecznym linkiem do ustawienia hasła i aktywacji konta. Hasło nie jest ustawiane tutaj.'],
            // Set-password (invite acceptance) page
            ['en', 'setPassword.title', 'Set your password'],
            ['pl', 'setPassword.title', 'Ustaw swoje hasło'],
            ['en', 'setPassword.subtitle', 'Choose a password to activate your account.'],
            ['pl', 'setPassword.subtitle', 'Wybierz hasło, aby aktywować swoje konto.'],
            ['en', 'setPassword.passwordLabel', 'Password'],
            ['pl', 'setPassword.passwordLabel', 'Hasło'],
            ['en', 'setPassword.confirmLabel', 'Confirm password'],
            ['pl', 'setPassword.confirmLabel', 'Potwierdź hasło'],
            ['en', 'setPassword.show', 'Show password'],
            ['pl', 'setPassword.show', 'Pokaż hasło'],
            ['en', 'setPassword.submit', 'Set password'],
            ['pl', 'setPassword.submit', 'Ustaw hasło'],
            ['en', 'setPassword.saving', 'Saving…'],
            ['pl', 'setPassword.saving', 'Zapisywanie…'],
            ['en', 'setPassword.validating', 'Validating your invitation…'],
            ['pl', 'setPassword.validating', 'Weryfikowanie zaproszenia…'],
            ['en', 'setPassword.invalidTitle', 'Invalid invitation'],
            ['pl', 'setPassword.invalidTitle', 'Nieprawidłowe zaproszenie'],
            ['en', 'setPassword.invalid', 'This invitation link is invalid or has already been used.'],
            ['pl', 'setPassword.invalid', 'Ten link zaproszenia jest nieprawidłowy lub został już użyty.'],
            ['en', 'setPassword.backToLogin', 'Back to login'],
            ['pl', 'setPassword.backToLogin', 'Powrót do logowania'],
            ['en', 'setPassword.successTitle', 'Password set'],
            ['pl', 'setPassword.successTitle', 'Hasło ustawione'],
            ['en', 'setPassword.successBody', 'Your password has been set. You can now sign in.'],
            ['pl', 'setPassword.successBody', 'Twoje hasło zostało ustawione. Możesz się teraz zalogować.'],
            ['en', 'setPassword.goToLogin', 'Go to login'],
            ['pl', 'setPassword.goToLogin', 'Przejdź do logowania'],
            ['en', 'setPassword.tooShort', 'Password must be at least 8 characters.'],
            ['pl', 'setPassword.tooShort', 'Hasło musi mieć co najmniej 8 znaków.'],
            ['en', 'setPassword.mismatch', 'Passwords do not match.'],
            ['pl', 'setPassword.mismatch', 'Hasła nie są takie same.'],
            ['en', 'setPassword.error', 'Could not set your password. Please try again.'],
            ['pl', 'setPassword.error', 'Nie udało się ustawić hasła. Spróbuj ponownie.'],
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
            'users.form.firstName',
            'users.form.lastName',
            'setPassword.title',
            'setPassword.subtitle',
            'setPassword.passwordLabel',
            'setPassword.confirmLabel',
            'setPassword.show',
            'setPassword.submit',
            'setPassword.saving',
            'setPassword.validating',
            'setPassword.invalidTitle',
            'setPassword.invalid',
            'setPassword.backToLogin',
            'setPassword.successTitle',
            'setPassword.successBody',
            'setPassword.goToLogin',
            'setPassword.tooShort',
            'setPassword.mismatch',
            'setPassword.error',
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
