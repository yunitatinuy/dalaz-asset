<?php

class CustomTCPDF extends TCPDF
{
    public $logoPath;
    public $footerImagePath;
    public $headerTitle = 'Data Teknis Peralatan';

    public function Header()
    {
        if ($this->logoPath && file_exists($this->logoPath)) {
            $logoWidth = 20;
            $marginRight = $this->getMargins()['right'];
            $x = $this->getPageWidth() - $marginRight - $logoWidth;
            $this->Image($this->logoPath, $x, 10, $logoWidth, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }

        $this->SetFont('helvetica', 'B', 14);
        $this->SetY(15);
        $this->SetX($this->getMargins()['left']);
        $this->Cell(0, 10, $this->headerTitle, 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $this->SetY(40);
    }

    public function Footer()
    {
        $this->SetY(-45);
        $marginLeft  = $this->getMargins()['left'];
        $marginRight = $this->getMargins()['right'];
        $footerWidth = $this->getPageWidth() - $marginLeft - $marginRight;

        if ($this->footerImagePath && file_exists($this->footerImagePath)) {
            // Gambar footer
            $this->Image($this->footerImagePath, $marginLeft, $this->GetY(), $footerWidth, 0, '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        } else {
            // Fallback teks jika gambar tidak tersedia
            $this->SetFont('helvetica', '', 7);
            $this->SetX($marginLeft);
            $footerText  = "PT DALAZ Teknik Utama | Lytech Industrial Park Blok E2-05, Batam Kota\n";
            $footerText .= "T: +62 778 741 8877 | Website: www.dalaz.co.id";
            $this->MultiCell($footerWidth, 3, $footerText, 0, 'C');
        }

        // Nomor Halaman
        $this->SetY(-15);
        $this->SetX(-40);
        $this->SetFont('helvetica', '', 8);
        $this->Cell(25, 5, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}

class PDFHelper
{
    private $pdf;
    private $pageWidth;
    private $marginLeft = 15;
    private $marginRight = 15;
    private $marginTop = 40;
    private $marginBottom = 55;

    public function __construct()
    {
        $this->pdf = new CustomTCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $this->pdf->setPrintHeader(true);
        $this->pdf->setPrintFooter(true);
        $this->pdf->SetMargins($this->marginLeft, $this->marginTop, $this->marginRight);
        $this->pdf->SetAutoPageBreak(true, $this->marginBottom);
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->pageWidth = $this->pdf->getPageWidth();
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->setCellPaddings(1.5, 1.5, 1.5, 1.5); 
    }

    public function setupHeaderFooter($title = 'Data Teknis Peralatan', $logoPath = null, $footerImagePath = null)
    {
        $this->pdf->headerTitle = $title;
        $this->pdf->logoPath = $logoPath;
        $this->pdf->footerImagePath = $footerImagePath;
        $this->pdf->AddPage();
    }

    // cek apakah perlu page break sebelum menambahkan konten
    public function checkPageBreak($heightNeeded)
    {
        $currentY = $this->pdf->GetY();
        // Batas bawah halaman (Total tinggi - Margin Bawah)
        $pageBreakTrigger = $this->pdf->getPageHeight() - $this->marginBottom;

        // Jika (Posisi Sekarang + Tinggi yg dibutuhkan) melebihi batas, buat halaman baru
        if (($currentY + $heightNeeded) > $pageBreakTrigger) {
            $this->pdf->AddPage();
        }
    }

    public function addSection($title)
    {
        $this->pdf->SetFillColor(220, 220, 220);
        $this->pdf->SetLineWidth(0.3);
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetFont('helvetica', 'B', 10);

        $contentWidth = $this->pageWidth - $this->marginLeft - $this->marginRight;
        $this->pdf->Cell($contentWidth, 7, $title, 1, 1, 'L', true);
    }

    public function addInfoRow($label, $value, $labelWidth = 55)
    {
        $this->pdf->SetLineWidth(0.3);
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetFillColor(255, 255, 255);

        $contentWidth = $this->pageWidth - $this->marginLeft - $this->marginRight;
        $valueWidth = $contentWidth - $labelWidth;

        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->Cell($labelWidth, 6, $label, 1, 0, 'L', true);

        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->Cell($valueWidth, 6, $value, 1, 1, 'L', true);
    }

    // Langkah Pemakaian
    public function addHTMLBox($htmlContent)
    {
        $this->pdf->SetLineWidth(0.3);
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetFont('helvetica', '', 9);
        $this->pdf->SetFillColor(255, 255, 255);

        $contentWidth = $this->pageWidth - $this->marginLeft - $this->marginRight;

        // Bersihkan HTML
        $cleanHtml = preg_replace('/<p[^>]*>[\s|&nbsp;]*<\/p>/', '', $htmlContent);
        $cleanHtml = preg_replace('/^(<br\s*\/?>)+/', '', $cleanHtml);
        $cleanHtml = preg_replace('/(<br\s*\/?>)+$/', '', $cleanHtml);
        $cleanHtml = trim($cleanHtml);

        if (empty(strip_tags($cleanHtml, '<img>'))) {
            $this->pdf->Cell($contentWidth, 8, 'Tidak ada data', 1, 1, 'L', true);
        } else {
            // Simpan setting lama
            $oldPad = $this->pdf->getCellPaddings();

            // Set Padding & Line Height agar rapat
            $this->pdf->setCellPaddings(1.5, 1, 1.5, 1);
            $this->pdf->setCellHeightRatio(1.1); // Jarak antar baris teks

            $style = '<style>p, div, li, h1, h2, h3 { margin: 0; padding: 0; } ul, ol { margin-top: 0; margin-bottom: 0; padding-left: 15px; }</style>';
            $finalHtml = $style . '<div>' . $cleanHtml . '</div>';

            $this->pdf->writeHTMLCell($contentWidth, 0, '', '', $finalHtml, 1, 1, true, true, 'L', true);

            // Restore
            $this->pdf->setCellPaddings($oldPad['L'], $oldPad['T'], $oldPad['R'], $oldPad['B']);
            $this->pdf->setCellHeightRatio(1.25);
        }

        $this->pdf->Ln(2);
    }

    public function addImagesAsHTML($images, $columns = 4)
    {
        if (empty($images)) {
            $this->addHTMLBox('Tidak ada dokumentasi');
            return;
        }

        $widthPercent = floor(100 / $columns);

        $html = '<table border="0" cellpadding="0" cellspacing="2" style="width:100%;">';
        $html .= '<tr>';

        $count = 0;
        foreach ($images as $imgPath) {
            if (file_exists($imgPath)) {
                if ($count > 0 && $count % $columns == 0) {
                    $html .= '</tr><tr>';
                }

                $safePath = str_replace('\\', '/', $imgPath);

                list($origW, $origH) = @getimagesize($imgPath);
                $sizeAttr = 'width="135"'; // Default 4 kolom
                if ($origW && $origH) {
                    if ($origH > $origW) {
                        $sizeAttr = 'height="120"';
                    } else {
                        $sizeAttr = 'width="165"';
                    }
                }

                $html .= '<td width="' . $widthPercent . '%" align="center" valign="middle">';
                $html .= '<img src="' . $safePath . '" ' . $sizeAttr . ' border="0" />';
                $html .= '</td>';

                $count++;
            }
        }

        while ($count % $columns != 0) {
            $html .= '<td width="' . $widthPercent . '%"></td>';
            $count++;
        }

        $html .= '</tr></table>';

        $this->addHTMLBox($html);
    }

    public function output($filename = 'document.pdf')
    {
        return $this->pdf->Output($filename, 'D');
    }

    public function getPDF()
    {
        return $this->pdf;
    }
}
