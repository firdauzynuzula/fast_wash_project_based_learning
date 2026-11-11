<?php
// includes/mailer.php
// Layanan pengiriman email untuk Fast Wash PBL menggunakan Brevo (Sendinblue) REST API.
// Menggunakan HTTPS (port 443) via native cURL PHP — kebal blokir port ISP lokal.

function kirim_email_reset_password(string $toEmail, string $toName, string $resetLink): array
{
    $config    = require __DIR__ . '/config.php';
    $apiKey    = trim($config['brevo_api_key'] ?? '');
    $fromEmail = trim($config['mail_from_email'] ?? 'support@fastwash.web.id');
    $fromName  = trim($config['mail_from_name'] ?? 'Fast Wash Official');

    // Waktu pengiriman (WIB) sebagai pembeda subjek & pencegah pemotongan (trim) oleh Gmail
    $nowWib   = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $waktuStr = $nowWib->format('H:i:s \W\I\B');
    $tglStr   = $nowWib->format('d M Y, H:i:s \W\I\B');

    // Template HTML Email yang modern, responsif & bersih
    $subject  = "Atur Ulang Kata Sandi - Fast Wash [{$waktuStr}]";
    $htmlBody = "
    <!DOCTYPE html>
    <html lang=\"id\">
    <head><meta charset=\"UTF-8\"></head>
    <body style=\"margin:0;padding:24px;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;\">
      <table align=\"center\" width=\"100%\" style=\"max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.06);border:1px solid #e2e8f0;\" cellpadding=\"0\" cellspacing=\"0\">
        <tr>
          <td style=\"background:#d9e8f8;padding:28px 32px;text-align:left;\">
            <div style=\"font-size:22px;font-weight:bold;color:#1668dc;\">Fast Wash</div>
            <div style=\"font-size:13px;color:#5b6b82;margin-top:4px;\">Handled with care &mdash; Layanan Laundry Modern</div>
          </td>
        </tr>
        <tr>
          <td style=\"padding:32px;\">
            <h2 style=\"margin:0 0 16px;color:#16305b;font-size:20px;\">Permintaan Atur Ulang Kata Sandi</h2>
            <p style=\"color:#475569;font-size:15px;line-height:1.6;margin:0 0 20px;\">
              Halo <strong>" . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . "</strong>,<br>
              Kami menerima permintaan untuk mengatur ulang kata sandi akun Fast Wash Anda. Klik tombol di bawah ini untuk melanjutkan:
            </p>
            <div style=\"text-align:center;margin:30px 0;\">
              <a href=\"{$resetLink}\" style=\"background:#1668dc;color:#ffffff;text-decoration:none;padding:13px 28px;border-radius:10px;font-weight:600;font-size:15px;display:inline-block;box-shadow:0 2px 6px rgba(22,104,220,0.3);\">
                Atur Ulang Kata Sandi
              </a>
            </div>
            <p style=\"color:#64748b;font-size:13px;line-height:1.5;margin:24px 0 0;\">
              Tautan ini hanya berlaku selama <strong>1 jam</strong>. Jika Anda tidak merasa melakukan permintaan ini, silakan abaikan email ini dengan aman.
            </p>
            <p style=\"color:#94a3b8;font-size:11px;margin:16px 0 0;\">
              Waktu permintaan: {$tglStr}
            </p>
          </td>
        </tr>
      </table>
    </body>
    </html>
    ";

    // Jika BREVO_API_KEY di .env belum diisi -> aktifkan Mode Demo / Simulasi
    if (empty($apiKey)) {
        return [
            'success'   => true,
            'demo_mode' => true,
            'message'   => 'Mode Demo: BREVO_API_KEY di file .env belum diisi. Tautan reset berhasil dibuat dan dapat langsung diuji.',
            'link'      => $resetLink,
        ];
    }

    // Kirim via Brevo REST API v3
    $payload = [
        'sender'      => [
            'name'  => $fromName,
            'email' => $fromEmail,
        ],
        'to'          => [
            [
                'email' => $toEmail,
                'name'  => $toName,
            ]
        ],
        'subject'     => $subject,
        'htmlContent' => $htmlBody,
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        error_log("Brevo cURL Error: " . $curlErr);
        return [
            'success'   => false,
            'demo_mode' => true,
            'message'   => "Gagal menghubungi Brevo API ({$curlErr}). Tautan dialihkan ke Mode Demo.",
            'link'      => $resetLink,
        ];
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        $data = json_decode($response, true);
        return [
            'success'    => true,
            'demo_mode'  => false,
            'message_id' => $data['messageId'] ?? '',
            'message'    => "Email verifikasi berhasil dikirim ke {$toEmail} melalui Brevo API!"
        ];
    }

    // Jika Brevo mengembalikan error (misal API key salah atau email pengirim belum diverifikasi)
    $errorData = json_decode($response, true);
    $detailMsg = $errorData['message'] ?? $response;
    error_log("Brevo API Error (HTTP {$httpCode}): " . $detailMsg);

    return [
        'success'   => false,
        'demo_mode' => true,
        'message'   => "Brevo API Error: {$detailMsg}",
        'link'      => $resetLink,
    ];
}
