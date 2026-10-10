<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Guards the schema against unreviewed additions: every table must be one we
 * designed (IA §4). A dependency that silently adds a migration fails this test.
 */
class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    /** DDL (rollback/migrate) implicitly commits on MySQL/MariaDB, so no wrapping transaction here. */
    protected $connectionsToTransact = [];

    public function test_exact_table_list(): void
    {
        $tables = collect(Schema::getTables())->pluck('name')
            ->reject(fn (string $name) => str_starts_with($name, 'sqlite_'))
            ->sort()->values()->all();

        $this->assertSame([
            'activity_logs', 'article_categories', 'article_category', 'article_tag', 'articles',
            'cache', 'cache_locks', 'certifications', 'contact_messages', 'educations', 'experiences',
            'media', 'migrations', 'password_reset_tokens', 'profiles', 'project_categories',
            'project_category', 'project_media', 'projects', 'redirects', 'service_groups', 'services',
            'sessions', 'settings', 'skills', 'tags', 'testimonials', 'users',
        ], $tables);
    }

    public function test_users_table_has_two_factor_and_last_login_columns(): void
    {
        foreach (['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'last_login_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), "users.{$column} missing");
        }
    }

    public function test_migrations_roll_back_cleanly(): void
    {
        $this->artisan('migrate:rollback', ['--force' => true])->assertSuccessful();
        $this->assertFalse(Schema::hasTable('articles'));
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->assertTrue(Schema::hasTable('articles'));
    }
}
