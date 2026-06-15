<?php

require_once __DIR__ . '/../../vendor/autoload.php';

class DefectReportPDF extends TCPDF
{
    private $logoPath;
    private $footerImgPath;

    public function __construct()
    {
        parent::__construct('P', 'mm', 'A4', true, 'UTF-8', false);

        $this->SetMargins(15, 30, 15);
        $this->SetAutoPageBreak(true, 55);
        $this->setPrintHeader(true);
        $this->setPrintFooter(true);

        $baseDir = dirname(__DIR__, 2);
        $this->logoPath = $baseDir . '/public/images/logo.png';
        $this->footerImgPath = $baseDir . '/public/images/footercomplaint.png';
    }

    public function Header()
    {
        $logoTag = '';
        if (file_exists($this->logoPath)) {
            $logoTag = '<img src="' . $this->logoPath . '" height="50" border="0" />';
        }

        $html = '
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td width="55%" style="vertical-align: bottom;">
                    <span style="font-size: 20pt; font-weight: bold; font-family: helvetica;">Defect Equipment Report</span>
                </td>
                <td width="45%" align="right" style="vertical-align: top;">
                    ' . $logoTag . '
                </td>
            </tr>
        </table>';

        $this->SetY(10);
        $this->writeHTML($html, true, false, true, false, '');
    }

    public function Footer()
    {
        $this->SetY(-50);
        $this->SetFont('helvetica', '', 8);

        $footerTag = '';
        if (file_exists($this->footerImgPath)) {
            $footerTag = '<img src="' . $this->footerImgPath . '" width="200" height="auto" border="0" />';
        }

        $addressText = '
        <b>Head Office</b> - Lytech Industrial Park E2-05, Batam Center, Batam, Riau Islands.<br>
        <b>Branch Office</b> - U Town Bintaro Blok A No 18, Sawah Lama, Ciputat, Tangerang Selatan, Banten 15413.<br>
        T: +62 778 741 8871 P: +62 81372855990<br>
        Website: www.dalaz.co.id | info.sales@dalaz.co.id';

        $html = '
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td colspan="2" style="padding-bottom: 5px;">
                    ' . $footerTag . '
                </td>
            </tr>
            <tr>
                <td width="75%" style="font-size: 7pt; line-height: 1.3; vertical-align: top;">
                    ' . $addressText . '
                </td>
                <td width="25%" align="right" style="vertical-align: bottom; font-weight: bold;">
                    Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages() . '
                </td>
            </tr>
        </table>';

        $this->writeHTML($html, true, false, true, false, '');
    }

    public function generate($data)
    {
        $this->AddPage();
        $this->SetFont('helvetica', '', 9);

        $bg = 'background-color: #E5E5E5;';
        $bold = 'font-weight: bold;';
        $center = 'text-align: center;';
        $tableAttr = 'border="1" cellpadding="5" cellspacing="0" width="100%"';

        // Data Processing
        $name = htmlspecialchars($data['user_name'] ?? '-');
        $adminName = htmlspecialchars($data['admin_name'] ?? 'Administrator');
        $controlNo = htmlspecialchars($data['control_no'] ?? '-');
        $empNo = htmlspecialchars($data['employee_no'] ?? '-');

        // Tanggal Laporan (User)
        $dateReported = (!empty($data['return_date'])) ? date('d F Y', strtotime($data['return_date'])) : '-';

        // Tanggal Review (Admin) - check_date
        $dateReviewed = (!empty($data['check_date'])) ? date('d F Y', strtotime($data['check_date'])) : '................';
        $time = (!empty($data['return_time'])) ? date('H.i', strtotime($data['return_time'])) : '-';
        $position = htmlspecialchars($data['position'] ?? '-');
        $location = htmlspecialchars($data['location_name'] ?? '-');

        // No Control
        $noteHtml = '';
        if (empty($data['control_no']) || $data['control_no'] === '-') {
            $noteHtml = '<div style="font-size: 8px; text-align: right; margin-top: 2px; font-style: italic; color: #555;">*Leave this field empty, to be filled by the store person</div>';
        }

        // 1. INFO TABLE
        $htmlInfo = '
        <table ' . $tableAttr . ' style="border-collapse: collapse;">
            <tr>
                <td width="15%" style="' . $bg . ' ' . $bold . '">Name</td>
                <td width="35%">' . $name . '</td>
                <td width="15%" style="' . $bg . ' ' . $bold . '">Date</td>
                <td width="35%">' . $dateReported . '</td>
            </tr>
            <tr>
                <td style="' . $bg . ' ' . $bold . '">Employee No.</td>
                <td>' . $empNo . '</td>
                <td style="' . $bg . ' ' . $bold . '">Time</td>
                <td>' . $time . '</td>
            </tr>
            <tr>
                <td style="' . $bg . ' ' . $bold . '">Position</td>
                <td>' . $position . '</td>
                <td style="' . $bg . ' ' . $bold . '">Location</td>
                <td>' . $location . '</td>
            </tr>
            <tr>
                <td style="' . $bg . ' ' . $bold . '">Control No.</td>
                <td colspan="3">' . $controlNo . '</td>
            </tr>
        </table>' . $noteHtml . '<br><br>';

        $this->writeHTML($htmlInfo, true, false, false, false, '');


        // 2. MAIN CONTENT
        $equipName = htmlspecialchars($data['equipment_name'] ?? '-');
        $serial = htmlspecialchars($data['serial_number'] ?? '-');
        $assetNum = htmlspecialchars($data['asset_number'] ?? '-');
        $type = htmlspecialchars($data['equipment_type'] ?? '-');
        $manufacturer = htmlspecialchars($data['manufacturer'] ?? '-');
        $defectContent = $data['defect_cause'] ? nl2br(htmlspecialchars($data['defect_cause'])) : '<span style="font-size: 8px; color: #777;">Describe the condition of malfunction, defective part, the circumstances under which its occurred and state the probable cause</span>';
        $treatmentContent = $data['treatment'] ? nl2br(htmlspecialchars($data['treatment'])) : '<span style="font-size: 8px; color: #777;">State the correction to stop the occurrence and recommendation to prevent recurrence</span>';
        $status = $data['check_status'] ?? '';
        $chk_repair   = ($status == 'repair')   ? '<b>[ X ]</b>' : '[   ]';
        $chk_replace  = ($status == 'replace')  ? '<b>[ X ]</b>' : '[   ]';
        $chk_disposal = ($status == 'disposal') ? '<b>[ X ]</b>' : '[   ]';
        $check_date_display = (!empty($data['check_date'])) ? date('d-M-Y', strtotime($data['check_date'])) : '';

        $htmlContent = '
        <table ' . $tableAttr . '>
            <tr style="' . $bg . ' ' . $center . ' ' . $bold . '">
                <td colspan="4">Description of Equipment</td>
            </tr>
            <tr style="' . $center . ' ' . $bold . '">
                <td width="30%">Description</td>
                <td width="25%">Serial Number /<br>Asset Number</td>
                <td width="25%">Type / Model</td>
                <td width="20%">Manufacturer</td>
            </tr>
            <tr style="' . $center . '">
                <td height="35" style="vertical-align:middle;">' . $equipName . '</td>
                <td style="vertical-align:middle;">' . $serial . ' /<br><b>' . $assetNum . '</b></td>
                <td style="vertical-align:middle;">' . $type . '</td>
                <td style="vertical-align:middle;">' . $manufacturer . '</td>
            </tr>

            <tr style="' . $bg . ' ' . $center . ' ' . $bold . '">
                <td colspan="4"><b>Description and Cause of Defect</b></td>
            </tr>
            <tr>
                <td colspan="4" height="70" style="vertical-align: top;">' . $defectContent . '</td>
            </tr>

            <tr style=" ' . $bg . ' ' . $center . ' ' . $bold . '">
                <td colspan="4"><b>Treatment</b></td>
            </tr>
            <tr>
                <td colspan="4" height="70" style="vertical-align: top;">' . $treatmentContent . '</td>
            </tr>

            <tr style="' . $bg . ' ' . $center . ' ' . $bold . '">
                <td width="25%">Remedial Action</td>
                <td width="75%" colspan="3">Require / Taken</td>
            </tr>
            
            <tr style="' . $center . '">
                <td style="' . $bg . ' ' . $bold . ' vertical-align:middle;">Date of Checking</td>
                <td width="25%" style="vertical-align:middle;">Repair</td>
                <td width="25%" style="vertical-align:middle;">Replace</td>
                <td width="25%" style="vertical-align:middle;">Disposal / Scrap</td>
            </tr>
            
            <tr style="' . $center . '">
                <td height="30" style="vertical-align:middle;">' . $check_date_display . '</td>
                <td style="vertical-align:middle;">' . $chk_repair . '</td>
                <td style="vertical-align:middle;">' . $chk_replace . '</td>
                <td style="vertical-align:middle;">' . $chk_disposal . '</td>
            </tr>
        </table>
        
        <table ' . $tableAttr . ' nobr="true">
            <tr style="' . $bg . ' ' . $center . ' ' . $bold . '">
                <td width="25%">Reported by:</td>
                <td width="25%">Reviewed by:</td>
                <td width="25%">Approved by:</td>
                <td width="25%">Action by:</td>
            </tr>
            <tr style="' . $center . ' vertical-align: bottom;">
                <td height="60"><br><br><br><b>' . $name . '</b></td>
                <td height="60"><br><br><br><b>' . $adminName . '</b></td>
                <td height="60"><br><br><br></td>
                <td height="60"><br><br><br></td>
            </tr>
            <tr style="font-size: 8pt;">
                <td>Name: ' . $name . '</td>
                <td>Name: ' . $adminName . '</td>
                <td>Name:</td>
                <td>Name:</td>
            </tr>
            <tr style="font-size: 8pt;">
                <td>Date: ' . $dateReported . '</td>
                <td>Date: ' . $dateReviewed . '</td>
                <td>Date:</td>
                <td>Date:</td>
            </tr>
        </table>';

        $this->writeHTML($htmlContent, true, false, false, false, '');

        if (ob_get_length()) ob_clean();
        $filename = 'Defect_Report_' . (($data['control_no'] && $data['control_no'] !== '-') ? $data['control_no'] : 'Draft') . '.pdf';
        $this->Output($filename, 'D');
    }
}
