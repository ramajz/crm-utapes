#!/usr/bin/env python3
"""Filter Webhook_Logs.csv (326MB) — hanya baris dengan timestamp >= 2026-06-01.

Output ditulis dengan csv writer agar format (quote/escape) tetap konsisten
dengan parser PHP fgetcsv di ImportScalevOrders.
"""
import csv
import sys
from datetime import datetime

SRC = "database/imports/Webhook_Logs.csv"
DST = "/tmp/crm-utapes-webhook-juni2026.csv"
CUTOFF = datetime(2026, 6, 1)

def parse_ts(raw: str):
    raw = raw.strip()
    for fmt in ("%m/%d/%Y %H:%M:%S", "%m/%d/%Y"):
        try:
            return datetime.strptime(raw, fmt)
        except ValueError:
            continue
    return None

kept = 0
skipped = 0
no_date = 0

with open(SRC, newline="", encoding="utf-8", errors="replace") as fin, \
     open(DST, "w", newline="", encoding="utf-8") as fout:
    reader = csv.reader(fin)
    writer = csv.writer(fout, lineterminator="\r\n")
    header = next(reader, None)
    if header:
        writer.writerow(header)
    for row in reader:
        if not row:
            skipped += 1
            continue
        ts = parse_ts(row[0])
        if ts is None:
            no_date += 1
            skipped += 1
            continue
        if ts >= CUTOFF:
            writer.writerow(row)
            kept += 1
        else:
            skipped += 1

print(f"kept={kept} skipped_before_june={skipped - no_date} no_date={no_date}")
print(f"output: {DST}")
