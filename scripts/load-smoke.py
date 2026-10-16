#!/usr/bin/env python3
"""Read-only, bounded local/staging smoke test. This is not a production capacity guarantee."""
import argparse
import json
import statistics
import time
import urllib.request
from concurrent.futures import ThreadPoolExecutor

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--base-url', default='http://127.0.0.1:8000')
parser.add_argument('--clients', type=int, choices=[50, 100, 150], default=50)
parser.add_argument('--path', default='/shop')
args = parser.parse_args()
url = args.base_url.rstrip('/') + args.path

def request(_):
    start = time.perf_counter()
    try:
        with urllib.request.urlopen(url, timeout=30) as response:
            body = response.read()
            valid = response.status == 200 and bool(body)
            if args.path == '/shop':
                valid = valid and b'product-grid' in body and b'The whole collection' in body
            return valid, (time.perf_counter() - start) * 1000
    except Exception:
        return False, (time.perf_counter() - start) * 1000

start = time.perf_counter()
with ThreadPoolExecutor(max_workers=args.clients) as pool:
    results = list(pool.map(request, range(args.clients)))
times = sorted(ms for _, ms in results)
failed = sum(not ok for ok, _ in results)
print(json.dumps({'requests': len(results), 'clients': args.clients, 'failed': failed,
                  'elapsed_s': round(time.perf_counter() - start, 2),
                  'median_ms': round(statistics.median(times), 1),
                  'p95_ms': round(times[min(len(times) - 1, int(len(times) * .95))], 1)}))
raise SystemExit(1 if failed else 0)
