<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260317000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create translation table and seed default EN/PL translations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE translation (
                id                INT AUTO_INCREMENT NOT NULL,
                locale            VARCHAR(5)   NOT NULL,
                translation_key   VARCHAR(200) NOT NULL,
                translation_value LONGTEXT     NOT NULL,
                created_at        DATETIME     NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at        DATETIME     NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNQ_LOCALE_KEY (locale, translation_key),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $now = date('Y-m-d H:i:s');

        $translations = [
            // ── Navigation ───────────────────────────────────────────────────
            ['en', 'nav.brand',       'My Dashboard'],
            ['pl', 'nav.brand',       'Mój Panel'],
            ['en', 'nav.dashboard',   'Dashboard'],
            ['pl', 'nav.dashboard',   'Panel'],
            ['en', 'nav.todos',       'To-Do'],
            ['pl', 'nav.todos',       'Zadania'],
            ['en', 'nav.shopping',    'Shopping'],
            ['pl', 'nav.shopping',    'Zakupy'],
            ['en', 'nav.events',      'My Events'],
            ['pl', 'nav.events',      'Moje Wydarzenia'],
            ['en', 'nav.map',         'Map'],
            ['pl', 'nav.map',         'Mapa'],
            ['en', 'nav.users',       'Users'],
            ['pl', 'nav.users',       'Użytkownicy'],
            ['en', 'nav.settings',    'Settings'],
            ['pl', 'nav.settings',    'Ustawienia'],
            ['en', 'nav.signOut',     'Sign out'],
            ['pl', 'nav.signOut',     'Wyloguj'],
            ['en', 'nav.language',    'Language'],
            ['pl', 'nav.language',    'Język'],
            // ── Login ─────────────────────────────────────────────────────────
            ['en', 'login.title',            'My Dashboard'],
            ['pl', 'login.title',            'Mój Panel'],
            ['en', 'login.subtitle',         'Sign in to your account'],
            ['pl', 'login.subtitle',         'Zaloguj się na swoje konto'],
            ['en', 'login.emailLabel',       'Email'],
            ['pl', 'login.emailLabel',       'Email'],
            ['en', 'login.passwordLabel',    'Password'],
            ['pl', 'login.passwordLabel',    'Hasło'],
            ['en', 'login.showPassword',     'Show password'],
            ['pl', 'login.showPassword',     'Pokaż hasło'],
            ['en', 'login.hidePassword',     'Hide password'],
            ['pl', 'login.hidePassword',     'Ukryj hasło'],
            ['en', 'login.signInBtn',        'Sign In'],
            ['pl', 'login.signInBtn',        'Zaloguj się'],
            ['en', 'login.signingIn',        'Signing in…'],
            ['pl', 'login.signingIn',        'Logowanie…'],
            ['en', 'login.requestAccess',    'Request Access'],
            ['pl', 'login.requestAccess',    'Poproś o dostęp'],
            ['en', 'login.accountInfo',      'Account creation is managed by administrators.'],
            ['pl', 'login.accountInfo',      'Tworzenie kont zarządzane jest przez administratorów.'],
            ['en', 'login.requestTitle',     'Request Access'],
            ['pl', 'login.requestTitle',     'Poproś o dostęp'],
            ['en', 'login.backToLogin',      '← Back to login'],
            ['pl', 'login.backToLogin',      '← Powrót do logowania'],
            ['en', 'login.requestSubtitle',  'Send your details to administrator for approval.'],
            ['pl', 'login.requestSubtitle',  'Wyślij swoje dane do administratora w celu zatwierdzenia.'],
            ['en', 'login.firstNameLabel',   'First name (optional)'],
            ['pl', 'login.firstNameLabel',   'Imię (opcjonalnie)'],
            ['en', 'login.lastNameLabel',    'Last name (optional)'],
            ['pl', 'login.lastNameLabel',    'Nazwisko (opcjonalnie)'],
            ['en', 'login.messageLabel',     'Request access message'],
            ['pl', 'login.messageLabel',     'Wiadomość z prośbą o dostęp'],
            ['en', 'login.cancelBtn',        'Cancel'],
            ['pl', 'login.cancelBtn',        'Anuluj'],
            ['en', 'login.sendRequestBtn',   'Send Request'],
            ['pl', 'login.sendRequestBtn',   'Wyślij zgłoszenie'],
            ['en', 'login.sending',          'Sending…'],
            ['pl', 'login.sending',          'Wysyłanie…'],
            // ── Settings ─────────────────────────────────────────────────────
            ['en', 'settings.title',                  '⚙️ Settings'],
            ['pl', 'settings.title',                  '⚙️ Ustawienia'],
            ['en', 'settings.access',                 '🔐 Access & Roles'],
            ['pl', 'settings.access',                 '🔐 Dostęp i Role'],
            ['en', 'settings.accessSubtitle',         'Manage roles and permissions'],
            ['pl', 'settings.accessSubtitle',         'Zarządzaj rolami i uprawnieniami'],
            ['en', 'settings.jwtSession',             '🪪 JWT Session'],
            ['pl', 'settings.jwtSession',             '🪪 Sesja JWT'],
            ['en', 'settings.jwtSessionSubtitle',     'Configure token expiry time'],
            ['pl', 'settings.jwtSessionSubtitle',     'Konfiguracja czasu ważności tokenu'],
            ['en', 'settings.notifications',          '🔔 Notifications'],
            ['pl', 'settings.notifications',          '🔔 Powiadomienia'],
            ['en', 'settings.notificationsSubtitle',  'Message templates and delivery channels'],
            ['pl', 'settings.notificationsSubtitle',  'Szablony wiadomości i kanały dostarczania'],
            ['en', 'settings.translations',           '🌐 Translations'],
            ['pl', 'settings.translations',           '🌐 Tłumaczenia'],
            ['en', 'settings.translationsSubtitle',   'Manage UI translation keys'],
            ['pl', 'settings.translationsSubtitle',   'Zarządzaj kluczami tłumaczeń'],
            // ── Users ─────────────────────────────────────────────────────────
            ['en', 'users.directory',          'Directory'],
            ['pl', 'users.directory',          'Katalog'],
            ['en', 'users.searchPlaceholder',  'Search by email or name'],
            ['pl', 'users.searchPlaceholder',  'Szukaj po emailu lub nazwie'],
            ['en', 'users.total',              'Total:'],
            ['pl', 'users.total',              'Łącznie:'],
            ['en', 'users.loading',            'Loading users…'],
            ['pl', 'users.loading',            'Ładowanie użytkowników…'],
            ['en', 'users.notFound',           'No users found.'],
            ['pl', 'users.notFound',           'Nie znaleziono użytkowników.'],
            ['en', 'users.email',              'Email'],
            ['pl', 'users.email',              'Email'],
            ['en', 'users.name',               'Name'],
            ['pl', 'users.name',               'Nazwa'],
            ['en', 'users.status',             'Status'],
            ['pl', 'users.status',             'Status'],
            ['en', 'users.role',               'Role'],
            ['pl', 'users.role',               'Rola'],
            ['en', 'users.actions',            'Actions'],
            ['pl', 'users.actions',            'Akcje'],
            ['en', 'users.edit',               'Edit'],
            ['pl', 'users.edit',               'Edytuj'],
            ['en', 'users.delete',             'Delete'],
            ['pl', 'users.delete',             'Usuń'],
            ['en', 'users.addUser',            '+ Add User'],
            ['pl', 'users.addUser',            '+ Dodaj Użytkownika'],
            // ── To-Do ─────────────────────────────────────────────────────────
            ['en', 'todo.title',    'My To-Do Lists'],
            ['pl', 'todo.title',    'Moje Listy Zadań'],
            ['en', 'todo.newList',  '+ New List'],
            ['pl', 'todo.newList',  '+ Nowa Lista'],
            ['en', 'todo.empty',    'No to-do lists yet. Create your first!'],
            ['pl', 'todo.empty',    'Brak list zadań. Stwórz swoją pierwszą!'],
            ['en', 'todo.addItem',  'Add item…'],
            ['pl', 'todo.addItem',  'Dodaj element…'],
            ['en', 'todo.dueLabel', 'Due:'],
            ['pl', 'todo.dueLabel', 'Termin:'],
            // ── Shopping ─────────────────────────────────────────────────────
            ['en', 'shopping.title',   'Shopping Lists'],
            ['pl', 'shopping.title',   'Listy Zakupów'],
            ['en', 'shopping.newList', '+ New List'],
            ['pl', 'shopping.newList', '+ Nowa Lista'],
            ['en', 'shopping.empty',   'No shopping lists yet.'],
            ['pl', 'shopping.empty',   'Brak list zakupów.'],
            // ── Dashboard ────────────────────────────────────────────────────
            ['en', 'dashboard.title',   'Dashboard'],
            ['pl', 'dashboard.title',   'Panel'],
            ['en', 'dashboard.welcome', 'Welcome back'],
            ['pl', 'dashboard.welcome', 'Witaj z powrotem'],
            // ── Translations admin ───────────────────────────────────────────
            ['en', 'translations.title',         'Translation Keys'],
            ['pl', 'translations.title',         'Klucze tłumaczeń'],
            ['en', 'translations.add',           '+ Add Translation'],
            ['pl', 'translations.add',           '+ Dodaj tłumaczenie'],
            ['en', 'translations.keyCol',        'Key'],
            ['pl', 'translations.keyCol',        'Klucz'],
            ['en', 'translations.localeCol',     'Locale'],
            ['pl', 'translations.localeCol',     'Język'],
            ['en', 'translations.valueCol',      'Value'],
            ['pl', 'translations.valueCol',      'Wartość'],
            ['en', 'translations.actionsCol',    'Actions'],
            ['pl', 'translations.actionsCol',    'Akcje'],
            ['en', 'translations.empty',         'No translations found.'],
            ['pl', 'translations.empty',         'Brak tłumaczeń.'],
            ['en', 'translations.filterLocale',  'Filter by locale'],
            ['pl', 'translations.filterLocale',  'Filtruj po języku'],
            ['en', 'translations.saveBtn',       'Save'],
            ['pl', 'translations.saveBtn',       'Zapisz'],
            ['en', 'translations.cancelBtn',     'Cancel'],
            ['pl', 'translations.cancelBtn',     'Anuluj'],
            ['en', 'translations.deleteConfirm', 'Delete this translation?'],
            ['pl', 'translations.deleteConfirm', 'Usunąć to tłumaczenie?'],
            // ── Common ────────────────────────────────────────────────────────
            ['en', 'common.loading',  'Loading…'],
            ['pl', 'common.loading',  'Ładowanie…'],
            ['en', 'common.error',    'An error occurred'],
            ['pl', 'common.error',    'Wystąpił błąd'],
            ['en', 'common.save',     'Save'],
            ['pl', 'common.save',     'Zapisz'],
            ['en', 'common.cancel',   'Cancel'],
            ['pl', 'common.cancel',   'Anuluj'],
            ['en', 'common.delete',   'Delete'],
            ['pl', 'common.delete',   'Usuń'],
            ['en', 'common.edit',     'Edit'],
            ['pl', 'common.edit',     'Edytuj'],
            ['en', 'common.confirm',  'Confirm'],
            ['pl', 'common.confirm',  'Potwierdź'],
            ['en', 'common.yes',      'Yes'],
            ['pl', 'common.yes',      'Tak'],
            ['en', 'common.no',       'No'],
            ['pl', 'common.no',       'Nie'],
            ['en', 'common.close',    'Close'],
            ['pl', 'common.close',    'Zamknij'],
            ['en', 'common.add',      'Add'],
            ['pl', 'common.add',      'Dodaj'],
            ['en', 'common.search',   'Search'],
            ['pl', 'common.search',   'Szukaj'],
        ];

        foreach ($translations as [$locale, $key, $value]) {
            $escapedValue = str_replace("'", "\\'", $value);
            $this->addSql(
                "INSERT INTO translation (locale, translation_key, translation_value, created_at, updated_at) "
                . "VALUES ('{$locale}', '{$key}', '{$escapedValue}', '{$now}', '{$now}')"
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE translation');
    }
}
