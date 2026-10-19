<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\ValidationException;
use PDOException;
use Tests\TestCase;

class SqliteTransactionTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_lock_conflict_retries_a_fully_rolled_back_transaction(): void
    {
        $attempts = 0;
        $result = app(TransactionService::class)->run(function () use (&$attempts) {
            $attempts++;
            $setting = StoreSetting::create(['store_name' => 'Attempt '.$attempts]);
            if ($attempts === 1) {
                throw new PDOException('database is locked');
            }

            return $setting;
        });

        $this->assertSame(2, $attempts);
        $this->assertSame('Attempt 2', $result->store_name);
        $this->assertSame(1, StoreSetting::count());
        $this->assertDatabaseMissing('store_settings', ['store_name' => 'Attempt 1']);
    }

    public function test_business_validation_is_not_retried_and_writes_are_rolled_back(): void
    {
        $attempts = 0;
        try {
            app(TransactionService::class)->run(function () use (&$attempts) {
                $attempts++;
                StoreSetting::create(['store_name' => 'Must roll back']);
                throw ValidationException::withMessages(['cart' => 'Sold out.']);
            });
            $this->fail('Validation should stop checkout.');
        } catch (ValidationException $exception) {
            $this->assertSame(['Sold out.'], $exception->errors()['cart']);
        }

        $this->assertSame(1, $attempts);
        $this->assertSame(0, StoreSetting::count());
    }
}
