<?php

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Logo\Logo;

class QRHelper
{
    public static function generate($assetCode)
    {
        try {
            $url = BASE_URL . '/show.php?code=' . urlencode($assetCode);

            $qrCode = QrCode::create($url)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->setSize(400)
                ->setMargin(15)
                ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

            $logoPath = BASE_PATH . '/public/images/logo.png';
            $logo = null;

            if (file_exists($logoPath)) {
                $logo = Logo::create($logoPath)
                    ->setResizeToWidth(80);
            }

            $writer = new PngWriter();
            $result = $writer->write($qrCode, $logo);

            return base64_encode($result->getString());
        } catch (Exception $e) {
            error_log("QR Generate Error: " . $e->getMessage());
            return null;
        }
    }

    public static function generateFile($assetCode, $savePath)
    {
        try {
            $url = BASE_URL . '/show.php?code=' . urlencode($assetCode);

            $qrCode = QrCode::create($url)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->setSize(400)
                ->setMargin(15)
                ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

            $logoPath = BASE_PATH . '/public/images/logo.png';
            $logo = null;

            if (file_exists($logoPath)) {
                $logo = Logo::create($logoPath)
                    ->setResizeToWidth(80);
            }

            $writer = new PngWriter();
            $result = $writer->write($qrCode, $logo);

            file_put_contents($savePath, $result->getString());
            return true;
        } catch (Exception $e) {
            error_log("QR File Generate Error: " . $e->getMessage());
            return false;
        }
    }

    public static function generateEquipment($assetNumber)
    {
        try {
            $url = BASE_URL . '/show_equipment.php?code=' . urlencode($assetNumber);

            $qrCode = QrCode::create($url)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->setSize(400)
                ->setMargin(15)
                ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

            $logoPath = BASE_PATH . '/public/images/logo.png';
            $logo = null;

            if (file_exists($logoPath)) {
                $logo = Logo::create($logoPath)
                    ->setResizeToWidth(80);
            }

            $writer = new PngWriter();
            $result = $writer->write($qrCode, $logo);

            return base64_encode($result->getString());
        } catch (Exception $e) {
            error_log("QR Equipment Generate Error: " . $e->getMessage());
            return null;
        }
    }

    public static function generateEquipmentFile($assetNumber, $savePath)
    {
        try {
            $url = BASE_URL . '/show_equipment.php?code=' . urlencode($assetNumber);

            $qrCode = QrCode::create($url)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->setSize(400)
                ->setMargin(15)
                ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));

            $logoPath = BASE_PATH . '/public/images/logo.png';
            $logo = null;

            if (file_exists($logoPath)) {
                $logo = Logo::create($logoPath)
                    ->setResizeToWidth(80);
            }

            $writer = new PngWriter();
            $result = $writer->write($qrCode, $logo);

            file_put_contents($savePath, $result->getString());
            return true;
        } catch (Exception $e) {
            error_log("QR Equipment File Generate Error: " . $e->getMessage());
            return false;
        }
    }
}
