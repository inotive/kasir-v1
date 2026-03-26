# Refund & Void: Kapan Dipakai dan Aturan Aman

Tujuan panduan ini: mencegah kebocoran uang dan menjaga laporan tetap bisa diaudit.

## Bedanya refund dan void (bahasa simpel)
- **Void (pembatalan)**: transaksi dibatalkan karena salah input atau transaksi tidak jadi berjalan.
- **Refund**: uang dikembalikan (sebagian atau penuh) setelah transaksi dianggap sudah terjadi.

## Keputusan cepat (paling sering dipakai)
1. **Pelanggan sudah bayar?**
   - Ya → pilih **Refund**
   - Tidak → pilih **Void/Pembatalan** sesuai kebijakan
2. **Uang yang dikembalikan penuh atau sebagian?**
   - Sebagian → **Refund parsial**
   - Penuh → **Refund penuh**

## Aturan praktis (disarankan)
1. Refund/void harus ada alasan yang jelas.
2. Refund/void nominal besar sebaiknya butuh persetujuan (PIN Manager).
3. Jangan “menghapus” transaksi secara manual. Lebih aman koreksi via fitur yang disediakan.

## Contoh kasus
### Kasus A: Salah input menu sebelum pembayaran selesai
Biasanya cukup koreksi/ulang transaksi sesuai prosedur kasir. Jika transaksi sudah tercatat sebagai valid, gunakan void sesuai kebijakan.

### Kasus B: Pelanggan sudah bayar tapi salah saji
Gunakan refund:
- **Refund parsial** jika hanya sebagian yang dikembalikan (mis. 1 item).
- **Refund penuh** jika seluruh transaksi dibatalkan.

## Dampak ke angka owner (penting)
- Refund akan mengurangi **Omzet (Net Sales)** di periode yang sama.
- Refund tidak boleh membuat angka menjadi negatif (sistem membatasi minimum 0 per transaksi).

> **Waspada:** refund tanpa alasan yang jelas adalah salah satu sumber kebocoran terbesar di bisnis F&B.

## Checklist audit mingguan untuk owner
- ☐ Apakah refund/void meningkat dibanding minggu lalu?
- ☐ Siapa user yang paling sering melakukan refund/void?
- ☐ Apakah ada pola: jam tertentu, shift tertentu, produk tertentu?
