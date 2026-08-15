#!/usr/bin/env python3
"""Backfill gross_revenue untuk order yang revenue-nya 0.

Order dibuat sebelum Juni (event order.created tersaring filter Juni+)
tapi dibayar Juni+; event payment_status_changed tidak membawa revenue.
Script ini mengambil gross_revenue dari event order.created di CSV PENUH
(Webhook_Logs.csv) lalu meng-update hanya order yang sudah ada di DB.
"""
import csv
import json
import sqlite3
import sys

CSV = "database/imports/Webhook_Logs.csv"
DB = "database/database.sqlite"

revenue_by_order = {}
n_created = 0

with open(CSV, newline="", encoding="utf-8", errors="replace") as f:
    reader = csv.reader(f)
    next(reader, None)
    for row in reader:
        if len(row) < 3:
            continue
        try:
            payload = json.loads(row[2])
        except Exception:
            continue
        if not isinstance(payload, dict):
            continue
        event = payload.get("event")
        data = payload.get("data")
        if event != "order.created" or not isinstance(data, dict):
            continue
        oid = data.get("order_id")
        if not oid:
            continue
        rev = data.get("gross_revenue")
        # gross_revenue di payload berupa string, mis. '575000.00'
        if isinstance(rev, (int, float)):
            rev_num = int(rev)
        elif isinstance(rev, str):
            try:
                rev_num = int(float(rev.replace(",", "")))
            except ValueError:
                rev_num = 0
        else:
            rev_num = 0
        # simpan nilai pertama (order.created paling awal) jika belum ada
        revenue_by_order.setdefault(str(oid), rev_num)
        n_created += 1

print(f"event order.created diproses: {n_created}, order unik: {len(revenue_by_order)}")

conn = sqlite3.connect(DB)
cur = conn.cursor()

cur.execute(
    "SELECT id, order_id, gross_revenue FROM orders WHERE gross_revenue = 0"
)
candidates = cur.fetchall()
print(f"order dengan gross_revenue=0 di DB: {len(candidates)}")

updated = 0
still_missing = 0
missing_ids = []
for pk, oid, current in candidates:
    rev = revenue_by_order.get(str(oid))
    if rev is not None:
        cur.execute(
            "UPDATE orders SET gross_revenue = ?, updated_at = datetime('now') WHERE id = ?",
            (rev, pk),
        )
        updated += 1
    else:
        still_missing += 1
        missing_ids.append(str(oid))

conn.commit()
conn.close()

print(f"di-backfill: {updated}")
print(f"tetap 0 (tidak ada event order.created): {still_missing}")
if missing_ids:
    print("missing ids:", missing_ids[:20])
