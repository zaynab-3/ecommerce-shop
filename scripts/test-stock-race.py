#!/usr/bin/env python3
"""Run 10 independent checkout processes competing for 3 units in a temporary SQLite database."""
import json
import os
import subprocess
import tempfile
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

root = Path(__file__).resolve().parent.parent
with tempfile.TemporaryDirectory(prefix='mesh-stock-race-') as temporary:
    database = Path(temporary) / 'test.sqlite'
    database.touch()
    environment = dict(os.environ, APP_ENV='testing', DB_CONNECTION='sqlite', DB_DATABASE=str(database),
                       CACHE_STORE='array', QUEUE_CONNECTION='sync', SESSION_DRIVER='array')

    def run(*arguments):
        return subprocess.run(['php', *arguments], cwd=root, env=environment, check=True,
                              capture_output=True, text=True).stdout.strip()

    run('artisan', 'migrate', '--force')
    run('tests/Support/stock-race-worker.php', 'init')
    with ThreadPoolExecutor(max_workers=10) as pool:
        outcomes = list(pool.map(lambda _: run('tests/Support/stock-race-worker.php'), range(10)))
    result = json.loads(run('tests/Support/stock-race-worker.php', 'check'))
    assert outcomes.count('placed') == 3, outcomes
    assert outcomes.count('sold_out') == 7, outcomes
    assert result == {'quantity': 0, 'orders': 3, 'ordered_units': 3}, result
    print('PASS: 10 concurrent checkouts; exactly 3 orders, 7 sold-out responses, zero remaining stock.')
