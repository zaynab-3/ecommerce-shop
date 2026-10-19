#!/usr/bin/env python3
"""Run 10 independent checkouts for 3 units in the dedicated MySQL *_race_test database."""
import json
import os
import subprocess
import tempfile
import time
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

root = Path(__file__).resolve().parent.parent
with tempfile.TemporaryDirectory(prefix='mesh-stock-race-') as temporary:
    ready_directory = Path(temporary) / 'ready'
    ready_directory.mkdir()
    start_file = Path(temporary) / 'start'
    environment = dict(os.environ, APP_ENV='testing', DB_CONNECTION='mysql', DB_URL='',
                       CACHE_STORE='array', QUEUE_CONNECTION='sync', SESSION_DRIVER='array',
                       MESH_RACE_READY_DIR=str(ready_directory), MESH_RACE_START_FILE=str(start_file))

    def run(*arguments):
        result = subprocess.run(['php', *arguments], cwd=root, env=environment,
                                capture_output=True, text=True)
        if result.returncode:
            detail = (result.stdout + '\n' + result.stderr).strip()
            if os.environ.get('GITHUB_ACTIONS') == 'true':
                escaped = detail.replace('%', '%25').replace('\r', '%0D').replace('\n', '%0A')
                print('::error title=Stock race worker failed::' + escaped, flush=True)
            raise RuntimeError(f'PHP worker exited with {result.returncode}:\n{detail}')
        return result.stdout.strip()

    environment['DB_DATABASE'] = run('tests/Support/stock-race-worker.php', 'database')
    try:
        run('tests/Support/stock-race-worker.php', 'init')
        with ThreadPoolExecutor(max_workers=10) as pool:
            futures = [pool.submit(run, 'tests/Support/stock-race-worker.php', f'worker-{i}') for i in range(10)]
            deadline = time.monotonic() + 15
            while len(list(ready_directory.iterdir())) < 10 and time.monotonic() < deadline:
                if any(future.done() for future in futures):
                    break
                time.sleep(.01)
            ready_count = len(list(ready_directory.iterdir()))
            start_file.touch()  # Release every initialized worker together, including on failure.
            outcomes = [future.result() for future in futures]
            assert ready_count == 10, f'Only {ready_count}/10 workers reached the start barrier.'
        result = json.loads(run('tests/Support/stock-race-worker.php', 'check'))
        assert outcomes.count('placed') == 3, outcomes
        assert outcomes.count('sold_out') == 7, outcomes
        assert result == {'quantity': 0, 'orders': 3, 'ordered_units': 3}, result
        print('PASS (MySQL): 10 concurrent checkouts; exactly 3 orders, 7 sold-out responses, zero remaining stock.')
    finally:
        run('tests/Support/stock-race-worker.php', 'cleanup')
