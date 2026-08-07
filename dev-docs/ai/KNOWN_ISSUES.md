# KNOWN_ISSUES

> **Status:** DATA FILE — hanya issue OPEN. Resolved >2 minggu → pindah ke RESOLVED.

---

## Open Issues

### ISSUE-001: Distribusi `status_fu=new` sangat timpang antar CS

- **Severity:** Medium
- **Status:** Open
- **Deskripsi:** Banyak lead di CS tertentu tidak di-update status FU-nya di AppScript (mis. Hafiz 1.344 new vs Oben 137 new). Ini kondisi data lapangan, bukan bug.
- **Dampak:** Load auto-assign (least_loaded) tidak seimbang karena beban historis.
- **Rencana:** Migrasi CS ke CRM-Utapes akan memperbaiki akurasi status. Auto-assign hanya mempertimbangkan lead `status_fu=new`.

### ISSUE-002: Revenue dari `leads.total_value` tidak reliabel

- **Severity:** High (jika salah dipakai)
- **Status:** Open (by design, sudah didokumentasikan di DATA_ACUAN.md)
- **Deskripsi:** Hanya 38 dari 823 lead paid yang punya `total_value > 0` (95% kosong di CSV AppScript).
- **Aturan:** Revenue/closing HARUS dari `orders.paid_time` + `gross_revenue` (Scalev). Jangan pakai `leads.total_value`.

### ISSUE-003: Branch handler CS masih kosong

- **Severity:** Low
- **Status:** Open
- **Deskripsi:** Kolom `branch_id` di `handlers` dan `leads` masih NULL untuk semua data (AppScript CSV tidak punya data cabang).
- **Rencana:** Sync NocoBase (M1) akan mengisi `branch_id` dari `cabang_id`.

### ISSUE-004: Ardha tidak ada di data AppScript Juli

- **Severity:** Low
- **Status:** Open (by design)
- **Deskripsi:** 12 CS utama PRD, tapi Ardha tidak muncul sebagai handler di CSV Juli → 11 CS aktif.
- **Catatan:** Bukan error; mungkin Ardha tidak handle lead Scalev atau data beda bulan.

### ISSUE-005: User CS dummy lama dihapus, handler `yusron`/`farhan` lowercase

- **Severity:** Low
- **Status:** Open
- **Deskripsi:** Nama handler `yusron` dan `farhan` tersimpan lowercase (ikut CSV), tidak konsisten dengan CS lain (title case). Tidak memengaruhi fungsi (is_active benar), hanya kosmetik di tampilan.
