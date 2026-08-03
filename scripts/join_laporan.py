#!/usr/bin/env python3
"""
CRM-Utapes Monthly Report Builder
=================================
Join NocoBase + AppScript CSV -> 1 master table -> semua metrik laporan bulanan.

Formulasi (kesepakatan DATA_ACUAN.md 2026-08-03):
- LEAD     = order masuk (tanggal_transaksi_masuk), exclude checkout=offline_store, semua cabang
- CLOSING  = order_status=success AND payment_status=paid, basis tanggal_payment,
             exclude checkout=offline_store, semua cabang
- REVENUE  = sum(total_pendapatan_kotor) dari CLOSING
- PER CS   = 12 CS utama (Hafiz5, Farhan8, Kiki38, Ardha9, Yusron24, Erpan4, Oben25,
             Andhi11, Peler=iwan18, Iqbal19, Babe=febri7, Dimas20)
- TRAFFIC & FUNNEL & NOTES = dari CSV AppScript (webhook Scalev)
- Join key = Order ID (CSV) == id_order (NocoBase)

Usage:
  python3 join_laporan.py --bulan 2026-07 [--csv /path/Leads_Jul_2026.csv] [--outdir /tmp/out]
"""
import argparse, csv, json, subprocess, sys, time, urllib.parse
from collections import Counter, defaultdict

BASE = "https://backoffice.utapesseken.co"
# 12 CS utama: user_id NocoBase -> nama laporan
CS_UTAMA = {
    5: "Hafiz", 8: "Farhan", 38: "Kiki", 9: "Ardha", 24: "Yusron", 4: "Erpan",
    25: "Oben", 11: "Andhi", 18: "Peler", 19: "Iqbal", 7: "Babe", 20: "Dimas",
}
# Alias AppScript -> nama laporan (untuk breakdown via CSV)
ALIAS = {"Lana": "Hafiz", "ikiobeng": "Oben", "febrifjr": "Babe", "erpann": "Erpan",
         "Ikbal cjr": "Iqbal", "Kiki ternyata": "Kiki", "Andhi Yanuar": "Andhi",
         "Rafli Bahar": "Rafli", "Peler": "Peler", "yusron": "Yusron", "farhan": "Farhan",
         "Dimas": "Dimas", "yusril khakiki": "Yusril", "Ilhan Manzis": "Ilhan"}

def sh(cmd, timeout=60):
    return subprocess.run(cmd, shell=True, capture_output=True, text=True, timeout=timeout).stdout

def login():
    resp = sh(f'''curl -s --max-time 20 -X POST "{BASE}/api/auth:signIn" \
        -H "Content-Type: application/json" -d '{{"account":"yusrilkhakiki@gmail.com","password":"Stumbling-Lumping1-Footman"}}' ''')
    return json.loads(resp)["data"]["token"]

def nb_count(token, filter_obj):
    q = urllib.parse.urlencode({"filter": json.dumps(filter_obj)})
    url = f"{BASE}/api/tb_transaksi_order:list?pageSize=1&{q}"
    for a in range(4):
        resp = sh(f'''curl -s --max-time 30 "{url}" -H "Authorization: Bearer {token}"''')
        try:
            return json.loads(resp)["meta"]["count"]
        except Exception:
            time.sleep(3)
    return None

def nb_pull_closing(token, month):
    """Pull semua closing bulan tsb (fields minimal)."""
    filt = urllib.parse.quote(json.dumps({
        "tanggal_payment": {"$includes": month},
        "order_status": "success", "payment_status": "paid",
        "checkout": {"$ne": "offline_store"},
    }))
    rows, page = [], 1
    while True:
        url = f"{BASE}/api/tb_transaksi_order:list?pageSize=500&page={page}&filter={filt}&fields[]=id_order&fields[]=checkout&fields[]=customer_service_id&fields[]=total_pendapatan_kotor&fields[]=tanggal_transaksi_masuk&fields[]=tanggal_payment"
        resp = sh(f'''curl -s --max-time 60 "{url}" -H "Authorization: Bearer {token}"''')
        try:
            d = json.loads(resp)
        except Exception:
            time.sleep(3); resp = sh(f'''curl -s --max-time 60 "{url}" -H "Authorization: Bearer {token}"'''); d = json.loads(resp)
        r = d.get("data", [])
        rows.extend(r)
        if len(r) < 500:
            break
        page += 1; time.sleep(1)
    return rows

def load_csv(path):
    with open(path, newline='', encoding='utf-8-sig') as f:
        return list(csv.DictReader(f))

def rupiah(n):
    return f"Rp {n:,.0f}".replace(",", ".")

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--bulan", required=True, help="YYYY-MM (contoh: 2026-07)")
    ap.add_argument("--csv", help="Path CSV AppScript bulan ybs (opsional)")
    ap.add_argument("--outdir", default="/tmp/crm-monthly")
    args = ap.parse_args()
    month = args.bulan
    outdir = args.outdir

    token = login()
    print(f"[1/6] Login OK. Bulan: {month}")

    # LEAD (order masuk, excl offline)
    lead = nb_count(token, {
        "tanggal_transaksi_masuk": {"$includes": month},
        "checkout": {"$ne": "offline_store"},
    })
    print(f"[2/6] LEAD (masuk, excl offline): {lead}")

    # CLOSING (success+paid, tanggal_payment, excl offline)
    closing_rows = nb_pull_closing(token, month)
    closing = len(closing_rows)
    revenue = sum(float(r.get("total_pendapatan_kotor") or 0) for r in closing_rows)
    print(f"[3/6] CLOSING: {closing} | REVENUE: {rupiah(revenue)}")

    # Per CS (12 utama)
    by_cs = Counter()
    rev_cs = defaultdict(float)
    non_utama = Counter()
    for r in closing_rows:
        cid = r.get("customer_service_id")
        if cid in CS_UTAMA:
            by_cs[CS_UTAMA[cid]] += 1
            rev_cs[CS_UTAMA[cid]] += float(r.get("total_pendapatan_kotor") or 0)
        else:
            non_utama[cid] += 1
    cs_total = sum(by_cs.values())
    print(f"[4/6] Closing 12 CS utama: {cs_total} (selisih dari total: {closing - cs_total})")

    # AppScript CSV: funnel, traffic, notes, join
    funnel = traffic = notes = None
    join_match = join_total = None
    if args.csv:
        rows = load_csv(args.csv)
        app_ids = {(r.get("Order ID") or "").strip() for r in rows if (r.get("Order ID") or "").strip()}
        nb_ids = {r.get("id_order") for r in closing_rows}
        join_match = len(app_ids & nb_ids)
        join_total = len(app_ids)
        funnel = Counter((r.get("Funnel Stage") or "").strip() for r in rows)
        traffic = Counter((r.get("Traffic Type") or "").strip() for r in rows)
        fu = Counter((r.get("Status FU") or "").strip() for r in rows)
        paid_app = sum(1 for r in rows if (r.get("Financial Status") or "").strip().lower() == "paid")
        notes = {"total_leads_app": len(rows), "paid_app": paid_app,
                 "join_match_closing": join_match, "status_fu_top": fu.most_common(6)}
        print(f"[5/6] AppScript: {len(rows)} leads, {paid_app} paid | join match: {join_match}")
    else:
        print("[5/6] Tanpa CSV AppScript (skip funnel/traffic)")

    # Output
    conv = closing / lead * 100 if lead else 0
    report = f"""# Laporan CRM-Utapes {month} (NocoBase + AppScript)

## Executive Summary
| Metrik | Nilai |
|--------|-------|
| Lead (order masuk, excl offline) | {lead} |
| Closing (success+paid, tanggal_payment) | {closing} |
| Konversi | {conv:.2f}% |
| Revenue (total_pendapatan_kotor) | {rupiah(revenue)} |
| Avg per order | {rupiah(revenue/closing) if closing else 0} |
| Closing 12 CS utama | {cs_total} |
"""
    if traffic:
        report += "\n## Traffic Source (AppScript)\n"
        for k, v in traffic.most_common():
            report += f"- {k}: {v} ({v/sum(traffic.values())*100:.1f}%)\n"
    if funnel:
        report += "\n## Funnel Stage (AppScript)\n"
        for k, v in funnel.most_common():
            report += f"- {k}: {v} ({v/sum(funnel.values())*100:.1f}%)\n"
    if notes:
        report += "\n## Status FU (AppScript)\n"
        for k, v in notes["status_fu_top"]:
            report += f"- {k}: {v}\n"
        report += f"\n## Join\n- AppScript leads: {notes['total_leads_app']}\n- Paid AppScript: {notes['paid_app']}\n- Closing yg match AppScript: {notes['join_match_closing']} (selisih {closing - notes['join_match_closing']} = di luar Scalev)\n"

    report += "\n## Per CS (12 utama, closing)\n"
    for name in sorted(by_cs, key=lambda x: -by_cs[x]):
        report += f"- {name}: {by_cs[name]} | {rupiah(rev_cs[name])}\n"

    print(f"[6/6] Selesai. Draft: {outdir}/laporan-{month}.md")
    with open(f"{outdir}/laporan-{month}.md", "w") as f:
        f.write(report)
    # CSV master
    if args.csv:
        master = f"{outdir}/master-{month}.csv"
        app_by_id = {r.get("Order ID"): r for r in load_csv(args.csv)}
        with open(master, "w", newline='') as f:
            w = csv.writer(f)
            w.writerow(["id_order", "closing", "revenue", "checkout", "customer_service_id",
                        "funnel_stage", "status_fu", "notes", "traffic_type", "handler"])
            for r in closing_rows:
                ap = app_by_id.get(r.get("id_order"), {})
                w.writerow([r.get("id_order"), 1, r.get("total_pendapatan_kotor"),
                            r.get("checkout"), r.get("customer_service_id"),
                            (ap.get("Funnel Stage") or "").strip(),
                            (ap.get("Status FU") or "").strip(),
                            (ap.get("Notes") or "").strip(),
                            (ap.get("Traffic Type") or "").strip(),
                            (ap.get("Handler (CS)") or "").strip()])
        print(f"CSV master: {master}")
    print(report)

if __name__ == "__main__":
    main()
