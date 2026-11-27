<?php

namespace App\Services;

use App\Support\Fmt;
use Illuminate\Http\Response;
use ZipArchive;

class ReportExportService
{
    /**
     * @param  array<string, mixed>  $report
     */
    public function download(array $report, string $format): Response
    {
        return $format === 'pdf'
            ? $this->pdf($report)
            : $this->xlsx($report);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function pdf(array $report): Response
    {
        $lines = [
            'SUPPLEMART - ' . config('supplemart.name', 'Alrisha Health Care'),
            'LAPORAN REKAP PENJUALAN',
            'Periode: ' . $report['label'],
            '----------------------------------------------------------------',
            'Total Penjualan : ' . Fmt::rp($report['total_sales']),
            'Jumlah Pesanan  : ' . $report['orders'],
            'Unit Terjual    : ' . $report['units'],
            'Rata-rata Order : ' . Fmt::rp($report['orders'] > 0 ? intdiv((int) $report['total_sales'], (int) $report['orders']) : 0),
            '',
            'PENJUALAN HARIAN (dalam ribuan rupiah)',
        ];

        foreach ($report['daily'] as [$day, $k]) {
            $lines[] = sprintf('  %-15s : %s', $day, Fmt::rp($k * 1000));
        }

        $lines[] = '';
        $lines[] = 'PRODUK TERLARIS';

        if (! $report['top']) {
            $lines[] = '  Belum ada transaksi pada periode ini.';
        } else {
            foreach ($report['top'] as [$name, $units, $sales]) {
                $lines[] = sprintf('  %-28s | %3d unit | %s', mb_substr($name, 0, 28), $units, Fmt::rp($sales));
            }
        }

        $binary = $this->buildSimplePdf($lines);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="rekap-penjualan-supplemart.pdf"',
        ]);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function buildSimplePdf(array $lines): string
    {
        $escape = fn (string $s): string => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);

        $stream = "BT\n/F1 10 Tf\n48 790 Td\n14 TL\n";
        foreach ($lines as $i => $line) {
            $safe = $escape(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $line) ?: $line);
            $stream .= ($i === 0 ? "({$safe}) Tj\n" : "T* ({$safe}) Tj\n");
        }
        $stream .= "ET";

        $objects = [
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>",
            4 => "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream",
            5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "{$num} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function xlsx(array $report): Response
    {
        $rows = [
            ['SUPPLEMART - Rekap Penjualan', $report['label'], '', ''],
            ['Total Penjualan', (float) $report['total_sales'], 'Pesanan Terhitung', (int) $report['orders']],
            ['Unit Terjual', (int) $report['units'], '', ''],
            ['', '', '', ''],
            ['Penjualan Harian', 'Nominal (Rp)', '', ''],
        ];

        foreach ($report['daily'] as [$day, $k]) {
            $rows[] = [$day, $k * 1000, '', ''];
        }

        $rows[] = ['', '', '', ''];
        $rows[] = ['Produk Terlaris', 'Unit Terjual', 'Total Penjualan (Rp)', ''];
        foreach ($report['top'] as [$name, $units, $sales]) {
            $rows[] = [$name, (int) $units, (float) $sales, ''];
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'sm_xlsx_');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Rekap Penjualan" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>');

        $sheetRows = '';
        $cols = ['A', 'B', 'C', 'D'];
        foreach ($rows as $rIdx => $row) {
            $rNum = $rIdx + 1;
            $sheetRows .= "<row r=\"{$rNum}\">";
            foreach ($row as $cIdx => $val) {
                if ($val === '' || $val === null) {
                    continue;
                }
                $ref = $cols[$cIdx] . $rNum;
                if (is_int($val) || is_float($val)) {
                    $sheetRows .= "<c r=\"{$ref}\"><v>{$val}</v></c>";
                } else {
                    $esc = htmlspecialchars((string) $val, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $sheetRows .= "<c r=\"{$ref}\" t=\"inlineStr\"><is><t>{$esc}</t></is></c>";
                }
            }
            $sheetRows .= '</row>';
        }

        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . "<sheetData>{$sheetRows}</sheetData>"
            . '</worksheet>');

        $zip->close();
        $content = file_get_contents($tmpFile) ?: '';
        @unlink($tmpFile);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="rekap-penjualan-supplemart.xlsx"',
        ]);
    }
}
