<!-- Email Wrapper -->
<div style="background:#f9f9f9;font-family:'Helvetica Neue',Arial,sans-serif;padding:40px 0;">
  <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08);">
    <!-- Header -->
    <div style="background:#d32f2f;color:#ffffff;text-align:center;padding:30px 20px;">
      <img src="{{ asset('images/logo-restaurant.png') }}" alt="Logo" style="height:48px;margin-bottom:12px;">
      <h1 style="margin:0;font-size:24px;font-weight:600;">Verifikasi Email Anda</h1>
    </div>

    <!-- Body -->
    <div style="padding:30px 40px;color:#333;line-height:1.6;">
      <p style="font-size:18px;margin-top:0;">Halo <strong>{{ $member->name }}</strong>,</p>
      <p>Terima kasih telah mendaftar sebagai member <strong>Restoran Kami</strong>. Untuk menyelesaikan pendaftaran, silakan verifikasi alamat email Anda dengan menekan tombol di bawah ini:</p>

      <div style="text-align:center;margin:30px 0;">
        <a href="{{ $verifyUrl }}" style="display:inline-block;background:#d32f2f;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:4px;font-weight:600;font-size:16px;box-shadow:0 2px 6px rgba(211,47,47,.3);">Verifikasi Email</a>
      </div>

      <p style="font-size:14px;color:#666;">Jika tombol tidak berfungsi, salin tautan berikut ke browser Anda:</p>
      <p style="word-break:break-all;font-size:13px;color:#555;background:#f5f5f5;padding:10px;border-radius:4px;">{{ $verifyUrl }}</p>

      <p style="margin-top:30px;font-size:14px;color:#777;">Jika Anda tidak merasa mendaftar, abaikan email ini dengan tenang.</p>
    </div>

    <!-- Footer -->
    <div style="background:#fafafa;text-align:center;padding:20px;font-size:12px;color:#999;">
      &copy; {{ date('Y') }} Restoran Kami. All rights reserved.
    </div>
  </div>
</div>
