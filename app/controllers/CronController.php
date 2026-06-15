<?php
// cron controller untuk mengirim email peringatan kalibrasi menggunakan PHPMailer 
// cronjob akan dijalankan setiap pukul 08:00 WIB
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class CronController extends Controller
{
    private $equipmentModel;
    private $userModel;

    public function __construct()
    {
        $this->equipmentModel = $this->model('Equipment');
        $this->userModel = $this->model('User');
    }

    public function sendCalibrationAlert()
    {
        $equipment = $this->equipmentModel->getAllWithCalibration();
        $alertList = [];
        $today = new DateTime();

        foreach ($equipment as $item) {
            $nextDueDateStr = $this->calculateNextDueDate($item['calibration_date'], $item['maintenance_frequency']);

            if (!$nextDueDateStr) continue;

            $dueDate = new DateTime($nextDueDateStr);
            $interval = $today->diff($dueDate);
            $daysDiff = (int)$interval->format('%r%a');

            // H-30
            if ($daysDiff == 30) {
                $alertList[] = [
                    'name' => $item['equipment_name'],
                    'code' => $item['asset_number'],
                    'date' => $nextDueDateStr,
                    'status' => 'WARNING (30 Days Left)',
                    'color' => '#fff3cd',
                    'text' => '#856404'
                ];
            }
            // H-7
            elseif ($daysDiff == 7) {
                $alertList[] = [
                    'name' => $item['equipment_name'],
                    'code' => $item['asset_number'],
                    'date' => $nextDueDateStr,
                    'status' => 'URGENT (1 Week Left)',
                    'color' => '#ffeeba',
                    'text' => '#d35400'
                ];
            }
            // Overdue
            elseif ($daysDiff < 0 && date('N') == 1) { // Hanya kirim overdue pada hari Senin
                $alertList[] = [
                    'name' => $item['equipment_name'],
                    'code' => $item['asset_number'],
                    'date' => $nextDueDateStr,
                    'status' => 'OVERDUE (' . abs($daysDiff) . ' days late)',
                    'color' => '#f8d7da',
                    'text' => '#721c24'
                ];
            }
        }

        if (!empty($alertList)) {
            $this->sendEmailViaPHPMailer($alertList);
        } else {
            echo "System Checked: No calibration alerts needed for today.";
        }
    }

    private function sendEmailViaPHPMailer($list)
    {
        // 1. AMBIL EMAIL ADMIN DARI DATABASE
        $admins = $this->userModel->getAdminEmails();

        if (empty($admins)) {
            echo "Failed: No admin emails found in database with role 'admin'.";
            return;
        }

        $mail = new PHPMailer(true);

        try {
            // Server Setting
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;

            // Pengirim (Tetap 1 Akun Sistem)
            $mail->setFrom(SMTP_USER, 'Dalaz Asset System');

            // Penerima (Looping ke Semua Admin)
            foreach ($admins as $admin) {
                if (!empty($admin['email'])) {
                    $mail->addAddress($admin['email'], $admin['full_name']);
                }
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = '[Dalaz Asset] Calibration Alert Report - ' . date('d M Y');

            // Body Email
            $body = '
            <html>
            <head>
                <style>
                    table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }
                    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                    th { background-color: #f2f2f2; }
                    .footer { margin-top: 20px; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <h3>⚠️ Calibration Reminder System</h3>
                <p>Hello Admins, here are the assets requiring calibration attention:</p>
                <table>
                    <thead>
                        <tr>
                            <th>Asset Name</th>
                            <th>Asset Code</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($list as $item) {
                $formattedDate = date('d M Y', strtotime($item['date']));
                $body .= "
                    <tr style='background-color: {$item['color']}; color: {$item['text']};'>
                        <td><strong>{$item['name']}</strong></td>
                        <td>{$item['code']}</td>
                        <td>{$formattedDate}</td>
                        <td>{$item['status']}</td>
                    </tr>";
            }

            $body .= '</tbody></table>
                <div class="footer">
                    <p>Automated message from Dalaz Asset Management System.<br>Please login to update the records.</p>
                </div>
            </body>
            </html>';

            $mail->Body = $body;
            $mail->send();

            echo 'Message has been sent successfully to ' . count($admins) . ' admins containing ' . count($list) . ' items.';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }

    private function calculateNextDueDate($lastCalDate, $frequencyString)
    {
        if (empty($lastCalDate) || $lastCalDate == '0000-00-00' || empty($frequencyString)) return null;
        try {
            $date = new DateTime($lastCalDate);
            $freqStr = strtolower($frequencyString);
            $number = (int) filter_var($freqStr, FILTER_SANITIZE_NUMBER_INT);
            if ($number > 0) {
                if (strpos($freqStr, 'year') !== false || strpos($freqStr, 'thn') !== false) {
                    $date->modify("+$number year");
                } else {
                    $date->modify("+$number month");
                }
                return $date->format('Y-m-d');
            }
        } catch (Exception $e) {
            return null;
        }
        return null;
    }
}
