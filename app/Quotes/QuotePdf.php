<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/setasign/fpdf/fpdf.php';

final class QuotePdf extends FPDF
{
    private array $quote = [];

    public function build(array $quote, array $items): string
    {
        $this->quote = $quote;
        $this->SetCreator('Go Creative Chile');
        $this->SetAuthor('Go Creative Chile');
        $this->SetTitle($this->encodeText('Cotizacion ' . $quote['quote_number']));
        $this->SetMargins(17, 18, 17);
        $this->SetAutoPageBreak(true, 24);
        $this->AliasNbPages();
        $this->AddPage();

        $this->customerBlock($quote);
        $this->introBlock($quote);
        $this->itemsBlock($items);
        $this->summaryBlock($quote);

        return $this->Output('S');
    }

    public function Header(): void
    {
        $this->SetFillColor(7, 17, 31);
        $this->Rect(0, 0, 210, 47, 'F');
        $this->SetFillColor(139, 234, 56);
        $this->Rect(0, 0, 210, 3.2, 'F');

        $logoPath = dirname(__DIR__, 2) . '/assets/img/logo-pdf.png';
        if (is_file($logoPath)) {
            $this->Image($logoPath, 17, 9.5, 50);
        }
        $this->SetTextColor(158, 174, 174);
        $this->SetFont('Helvetica', 'B', 6.5);
        $this->SetXY(17, 29.5);
        $this->Cell(100, 5, $this->encodeText('DISEÑO · TECNOLOGIA · CRECIMIENTO'), 0, 0);
        $this->SetTextColor(204, 215, 214);
        $this->SetFont('Helvetica', '', 6.3);
        $this->SetXY(17, 35);
        $this->Cell(96, 4, $this->encodeText('gocreative.cl · ' . SITE_EMAIL . ' · ' . SITE_PHONE_DISPLAY), 0, 0);

        $this->SetTextColor(139, 234, 56);
        $this->SetFont('Helvetica', 'B', 7);
        $this->SetXY(118, 12);
        $this->Cell(75, 5, 'PROPUESTA COMERCIAL', 0, 1, 'R');
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 15);
        $this->SetX(118);
        $this->Cell(75, 7, $this->encodeText($this->quote['quote_number'] ?? ''), 0, 1, 'R');
        $this->SetTextColor(173, 186, 187);
        $this->SetFont('Helvetica', '', 7.5);
        $this->SetX(118);
        $this->Cell(75, 5, $this->encodeText('Emitida ' . $this->date((string) ($this->quote['issue_date'] ?? ''))), 0, 1, 'R');
        $this->Ln(19);
    }

    public function Footer(): void
    {
        $this->SetY(-18);
        $this->SetDrawColor(216, 224, 219);
        $this->Line(17, $this->GetY(), 193, $this->GetY());
        $this->SetY(-14);
        $this->SetTextColor(110, 125, 130);
        $this->SetFont('Helvetica', '', 6.4);
        $this->Cell(132, 4, $this->encodeText('gocreative.cl · ' . SITE_EMAIL . ' · ' . SITE_PHONE_DISPLAY), 0, 0);
        $this->Cell(44, 4, $this->encodeText('Página ' . $this->PageNo() . ' de {nb}'), 0, 1, 'R');
        $this->SetX(17);
        $this->Cell(176, 4, $this->encodeText(SITE_CITY), 0, 0);
    }

    private function customerBlock(array $quote): void
    {
        $this->SetFillColor(242, 246, 242);
        $this->Rect(17, 52, 176, 31, 'F');
        $this->SetDrawColor(213, 223, 216);
        $this->Rect(17, 52, 176, 31, 'D');

        $this->SetTextColor(38, 148, 94);
        $this->SetFont('Helvetica', 'B', 6.8);
        $this->SetXY(23, 57);
        $this->Cell(80, 4, 'PREPARADA PARA', 0, 1);
        $this->SetTextColor(7, 17, 31);
        $this->SetFont('Helvetica', 'B', 12);
        $this->SetX(23);
        $this->Cell(95, 6, $this->encodeText((string) $quote['customer_name']), 0, 1);
        $this->SetTextColor(92, 108, 114);
        $this->SetFont('Helvetica', '', 8);
        $this->SetX(23);
        $clientLine = trim((string) ($quote['customer_company'] ?? ''));
        if (!empty($quote['customer_tax_id'])) {
            $clientLine .= ($clientLine !== '' ? ' · ' : '') . $quote['customer_tax_id'];
        }
        $this->Cell(95, 5, $this->encodeText($clientLine !== '' ? $clientLine : (string) $quote['customer_email']), 0, 1);

        $this->SetTextColor(38, 148, 94);
        $this->SetFont('Helvetica', 'B', 6.8);
        $this->SetXY(128, 57);
        $this->Cell(58, 4, 'VIGENCIA', 0, 1, 'R');
        $this->SetTextColor(7, 17, 31);
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetX(128);
        $this->Cell(58, 6, $this->encodeText('Hasta ' . $this->date((string) $quote['valid_until'])), 0, 1, 'R');
        $this->SetTextColor(92, 108, 114);
        $this->SetFont('Helvetica', '', 7.5);
        $this->SetX(128);
        $this->Cell(58, 5, $this->encodeText('Valores expresados en ' . $quote['currency']), 0, 1, 'R');
        $this->SetY(89);
    }

    private function introBlock(array $quote): void
    {
        $this->SetTextColor(38, 148, 94);
        $this->SetFont('Helvetica', 'B', 7);
        $this->Cell(176, 5, 'ALCANCE DE LA PROPUESTA', 0, 1);
        $this->SetTextColor(7, 17, 31);
        $this->SetFont('Helvetica', 'B', 16);
        $this->MultiCell(176, 7, $this->encodeText((string) $quote['title']), 0, 'L');
        if (!empty($quote['introduction'])) {
            $this->Ln(1);
            $this->SetTextColor(77, 94, 101);
            $this->SetFont('Helvetica', '', 8);
            $this->MultiCell(176, 4.5, $this->encodeText((string) $quote['introduction']), 0, 'L');
        }
        $this->Ln(3);
    }

    private function itemsBlock(array $items): void
    {
        $this->SetFillColor(7, 17, 31);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 7);
        $this->Cell(126, 9, ' SERVICIO / PRODUCTO', 0, 0, 'L', true);
        $this->Cell(20, 9, 'CANT.', 0, 0, 'C', true);
        $this->Cell(30, 9, 'TOTAL', 0, 1, 'R', true);

        foreach ($items as $index => $item) {
            $description = trim((string) ($item['description'] ?? ''));
            $lines = max(1, (int) ceil($this->GetStringWidth($this->encodeText($description)) / 116));
            $height = max(19, 13 + ($lines * 3.5));
            if ($this->GetY() + $height > 263) {
                $this->AddPage();
                $this->SetFillColor(7, 17, 31);
                $this->SetTextColor(255, 255, 255);
                $this->SetFont('Helvetica', 'B', 7);
                $this->Cell(126, 9, ' SERVICIO / PRODUCTO', 0, 0, 'L', true);
                $this->Cell(20, 9, 'CANT.', 0, 0, 'C', true);
                $this->Cell(30, 9, 'TOTAL', 0, 1, 'R', true);
            }

            $y = $this->GetY();
            $this->SetFillColor($index % 2 === 0 ? 248 : 242, $index % 2 === 0 ? 250 : 246, $index % 2 === 0 ? 247 : 242);
            $this->Rect(17, $y, 176, $height, 'F');
            $this->SetTextColor(38, 148, 94);
            $this->SetFont('Helvetica', 'B', 6.3);
            $type = $item['item_type'] === 'product' ? 'PRODUCTO' : 'SERVICIO';
            $this->SetXY(21, $y + 2.7);
            $this->Cell(115, 4, $type, 0, 1);
            $this->SetTextColor(7, 17, 31);
            $this->SetFont('Helvetica', 'B', 8.6);
            $this->SetX(21);
            $this->Cell(116, 5, $this->encodeText((string) $item['name']), 0, 1);
            if ($description !== '') {
                $this->SetTextColor(91, 106, 112);
                $this->SetFont('Helvetica', '', 6.8);
                $this->SetX(21);
                $this->MultiCell(116, 3.5, $this->encodeText($description), 0, 'L');
            }
            $this->SetTextColor(64, 79, 85);
            $this->SetFont('Helvetica', '', 8);
            $this->SetXY(143, $y + 6);
            $this->Cell(20, 7, $this->quantity((float) $item['quantity']), 0, 0, 'C');
            $this->SetTextColor(7, 17, 31);
            $this->SetFont('Helvetica', 'B', 9);
            $this->SetXY(163, $y + 6);
            $this->Cell(26, 7, $this->money((int) $item['line_total']), 0, 0, 'R');
            $this->SetY($y + $height);
            $this->SetDrawColor(220, 227, 223);
            $this->Line(17, $this->GetY(), 193, $this->GetY());
        }
        $this->Ln(4);
    }

    private function summaryBlock(array $quote): void
    {
        if ($this->GetY() > 218) {
            $this->AddPage();
            $this->SetY(55);
            $this->SetTextColor(38, 148, 94);
            $this->SetFont('Helvetica', 'B', 7);
            $this->Cell(176, 5, 'RESUMEN COMERCIAL', 0, 1);
            $this->Ln(2);
        }
        $summaryY = $this->GetY();
        $this->notesColumn($quote, $summaryY);
        $this->totalsColumn($quote, $summaryY);
    }

    private function totalsColumn(array $quote, float $y): void
    {
        $x = 111;
        $this->SetXY($x, $y);
        $this->SetFillColor(242, 246, 242);
        $this->SetTextColor(85, 101, 107);
        $this->SetFont('Helvetica', '', 8);
        $this->Cell(42, 8, 'Subtotal', 0, 0, 'L', true);
        $this->SetTextColor(7, 17, 31);
        $this->SetFont('Helvetica', 'B', 8);
        $this->Cell(40, 8, $this->money((int) $quote['subtotal']), 0, 1, 'R', true);
        if ((int) $quote['discount_amount'] > 0) {
            $this->SetX($x);
            $this->SetTextColor(85, 101, 107);
            $this->SetFont('Helvetica', '', 8);
            $this->Cell(42, 8, 'Descuento', 0, 0, 'L', true);
            $this->SetTextColor(38, 148, 94);
            $this->SetFont('Helvetica', 'B', 8);
            $this->Cell(40, 8, '- ' . $this->money((int) $quote['discount_amount']), 0, 1, 'R', true);
        }
        if ((int) $quote['tax_percent'] > 0) {
            $this->SetX($x);
            $this->SetTextColor(85, 101, 107);
            $this->SetFont('Helvetica', '', 8);
            $this->Cell(42, 8, 'IVA ' . (int) $quote['tax_percent'] . '%', 0, 0, 'L', true);
            $this->SetTextColor(7, 17, 31);
            $this->SetFont('Helvetica', 'B', 8);
            $this->Cell(40, 8, $this->money((int) $quote['tax_amount']), 0, 1, 'R', true);
        }
        $this->SetX($x);
        $this->SetFillColor(139, 234, 56);
        $this->SetTextColor(7, 17, 31);
        $this->SetFont('Helvetica', 'B', 9);
        $this->Cell(34, 12, 'TOTAL', 0, 0, 'L', true);
        $this->SetFont('Helvetica', 'B', 15);
        $this->Cell(48, 12, $this->money((int) $quote['total']), 0, 1, 'R', true);
    }

    private function notesColumn(array $quote, float $y): void
    {
        $this->SetXY(17, $y);
        foreach ([['title' => 'CONDICIONES COMERCIALES', 'value' => $quote['terms'] ?? ''], ['title' => 'OBSERVACIONES', 'value' => $quote['notes'] ?? '']] as $block) {
            if (trim((string) $block['value']) === '') {
                continue;
            }
            $this->SetTextColor(38, 148, 94);
            $this->SetFont('Helvetica', 'B', 6.5);
            $this->Cell(86, 4.5, $block['title'], 0, 1);
            $this->SetTextColor(72, 88, 95);
            $this->SetFont('Helvetica', '', 7);
            $this->MultiCell(86, 4, $this->encodeText((string) $block['value']), 0, 'L');
            $this->Ln(3);
        }
    }

    private function encodeText(string $value): string
    {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
        return $converted === false ? preg_replace('/[^\x20-\x7E]/', '', $value) ?? '' : $converted;
    }

    private function money(int $amount): string
    {
        return '$' . number_format($amount, 0, ',', '.');
    }

    private function quantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 2, ',', '.'), '0'), ',');
    }

    private function date(string $date): string
    {
        $timestamp = strtotime($date);
        return $timestamp ? date('d-m-Y', $timestamp) : $date;
    }
}

function quote_pdf_binary(array $quote, array $items): string
{
    $pdf = new QuotePdf('P', 'mm', 'A4');
    return $pdf->build($quote, $items);
}
