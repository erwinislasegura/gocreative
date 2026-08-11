<?php
declare(strict_types=1);

function email_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function email_layout(string $preheader, string $eyebrow, string $title, string $content, string $accent = '#8bea38'): string
{
    $year = date('Y');
    return '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;background:#eef2ee;color:#07111f;font-family:Arial,Helvetica,sans-serif">'
        . '<div style="display:none;max-height:0;overflow:hidden;opacity:0">' . email_escape($preheader) . '</div>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef2ee"><tr><td align="center" style="padding:28px 14px">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#fbfdfb;border:1px solid #d7e0da">'
        . '<tr><td style="padding:26px 32px;background:#07111f;border-top:5px solid ' . $accent . '">'
        . '<div style="color:#ffffff;font-size:22px;font-weight:800;letter-spacing:-1px">GO CREATIVE</div>'
        . '<div style="margin-top:5px;color:#9eaeae;font-size:10px;font-weight:700;letter-spacing:2px">DISEÑO · TECNOLOGÍA · CRECIMIENTO</div>'
        . '</td></tr><tr><td style="padding:38px 32px 16px">'
        . '<div style="margin-bottom:11px;color:#26945e;font-size:10px;font-weight:800;letter-spacing:2px">' . email_escape(mb_strtoupper($eyebrow)) . '</div>'
        . '<h1 style="margin:0;color:#07111f;font-size:30px;line-height:1.12;letter-spacing:-1px">' . email_escape($title) . '</h1>'
        . '</td></tr><tr><td style="padding:4px 32px 40px">' . $content . '</td></tr>'
        . '<tr><td style="padding:20px 32px;color:#78868b;background:#f3f6f2;border-top:1px solid #d7e0da;font-size:11px;line-height:1.6">'
        . '© ' . $year . ' Go Creative Chile · Los Ángeles, Biobío<br>Este correo fue generado desde nuestro sistema de gestión comercial.'
        . '</td></tr></table></td></tr></table></body></html>';
}

function hosting_notice_email(array $service, int $level, string $paymentUrl): array
{
    $labels = [
        1 => ['eyebrow' => 'Aviso 1 de renovación', 'title' => 'Tu servicio de hosting está próximo a renovar.', 'accent' => '#8bea38'],
        2 => ['eyebrow' => 'Aviso 2 de renovación', 'title' => 'Tu renovación de hosting sigue pendiente.', 'accent' => '#f1b84b'],
        3 => ['eyebrow' => 'Aviso de suspensión', 'title' => 'Evita la suspensión de tu servicio de hosting.', 'accent' => '#dc4f66'],
    ];
    $copy = $labels[$level] ?? $labels[1];
    $domain = $service['domain'] ?: $service['service_name'];
    $amount = payment_format_amount((int) $service['amount'], $service['currency']);
    $dueDate = date('d-m-Y', strtotime((string) $service['due_date']));
    $cycle = $service['billing_cycle'] === 'semiannual' ? 'Semestral' : 'Anual';

    $content = '<p style="margin:0 0 22px;color:#4e6068;font-size:15px;line-height:1.7">Hola ' . email_escape($service['customer_name']) . ', te escribimos para mantener activo y protegido el servicio asociado a <strong>' . email_escape($domain) . '</strong>.</p>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 24px;border:1px solid #d7e0da">'
        . '<tr><td style="padding:13px 16px;color:#718087;font-size:11px;border-bottom:1px solid #e2e8e4">SERVICIO</td><td align="right" style="padding:13px 16px;font-size:13px;font-weight:700;border-bottom:1px solid #e2e8e4">' . email_escape($service['plan_name']) . '</td></tr>'
        . '<tr><td style="padding:13px 16px;color:#718087;font-size:11px;border-bottom:1px solid #e2e8e4">CICLO / VENCIMIENTO</td><td align="right" style="padding:13px 16px;font-size:13px;font-weight:700;border-bottom:1px solid #e2e8e4">' . $cycle . ' · ' . $dueDate . '</td></tr>'
        . '<tr><td style="padding:16px;color:#718087;font-size:11px">TOTAL RENOVACIÓN</td><td align="right" style="padding:16px;color:#07111f;font-size:22px;font-weight:800">' . email_escape($amount) . '</td></tr></table>'
        . '<table role="presentation" cellspacing="0" cellpadding="0"><tr><td style="background:#8bea38"><a href="' . email_escape($paymentUrl) . '" style="display:inline-block;padding:16px 23px;color:#07111f;font-size:13px;font-weight:800;text-decoration:none">Pagar renovación de hosting →</a></td></tr></table>'
        . '<p style="margin:18px 0 0;color:#7b888d;font-size:11px;line-height:1.6">El botón abre directamente el checkout seguro de Flow. Go Creative no solicita ni almacena datos de tarjetas.</p>';

    return [
        'subject' => $copy['eyebrow'] . ': ' . $domain,
        'html' => email_layout($copy['title'], $copy['eyebrow'], $copy['title'], $content, $copy['accent']),
    ];
}

function quote_email(array $quote, string $publicUrl): array
{
    $total = payment_format_amount((int) $quote['total'], $quote['currency']);
    $content = '<p style="margin:0 0 20px;color:#4e6068;font-size:15px;line-height:1.7">Hola ' . email_escape($quote['customer_name']) . ', preparamos una propuesta clara para <strong>' . email_escape($quote['title']) . '</strong>. Encontrarás el PDF adjunto y también puedes revisarla en línea.</p>'
        . '<div style="margin:0 0 24px;padding:22px;background:#07111f;color:#ffffff;border-left:5px solid #8bea38">'
        . '<div style="color:#9fb0b0;font-size:10px;font-weight:700;letter-spacing:1.5px">PROPUESTA ' . email_escape($quote['quote_number']) . '</div>'
        . '<div style="margin-top:8px;font-size:28px;font-weight:800">' . email_escape($total) . '</div>'
        . '<div style="margin-top:6px;color:#b9c6c6;font-size:12px">Válida hasta el ' . date('d-m-Y', strtotime((string) $quote['valid_until'])) . '</div></div>'
        . '<table role="presentation" cellspacing="0" cellpadding="0"><tr><td style="background:#8bea38"><a href="' . email_escape($publicUrl) . '" style="display:inline-block;padding:16px 23px;color:#07111f;font-size:13px;font-weight:800;text-decoration:none">Ver y responder cotización →</a></td></tr></table>'
        . '<p style="margin:18px 0 0;color:#7b888d;font-size:11px;line-height:1.6">Si necesitas ajustar alcance, cantidades o plazos, responde directamente a este correo.</p>';

    return [
        'subject' => 'Cotización ' . $quote['quote_number'] . ' · ' . $quote['title'],
        'html' => email_layout('Nueva cotización de Go Creative', 'Propuesta comercial', 'Una propuesta pensada para avanzar.', $content),
    ];
}
