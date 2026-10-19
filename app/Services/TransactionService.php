<?php

namespace App\Services;

use Closure;
use Illuminate\Database\DetectsConcurrencyErrors;
use Illuminate\Support\Facades\DB;
use Throwable;

class TransactionService
{
    use DetectsConcurrencyErrors;

    public function run(Closure $operation): mixed
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'sqlite' || $connection->transactionLevel() > 0) {
            return $connection->transaction($operation, 5);
        }

        // Laravel uses deferred SQLite transactions on PHP 8.2/8.3. Competing
        // readers can fail to upgrade to a writer immediately; give the winning
        // checkout time to commit before retrying the entire rolled-back unit.
        $deadline = microtime(true) + 5;
        for ($attempt = 0; $attempt < 10; $attempt++) {
            try {
                return $connection->transaction($operation);
            } catch (Throwable $exception) {
                if (! $this->causedByConcurrencyError($exception) || $attempt === 9 || microtime(true) >= $deadline) {
                    throw $exception;
                }
                $delay = min(250_000, 10_000 * (2 ** $attempt));
                usleep(random_int($delay, (int) ($delay * 1.5)));
            }
        }
    }
}
