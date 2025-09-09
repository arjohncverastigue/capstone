<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer classes
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

function sendResetEmail($recipientEmail, $recipientName, $resetLink) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'arjohn818@gmail.com'; // your Gmail
        $mail->Password   = 'ktnowevklszqcnkw';    // your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Debugging (set to 0 in production)
        $mail->SMTPDebug  = 0; // use 2 if you want verbose logs

        // Recipients
        $mail->setFrom('arjohn818@gmail.com', 'LGU Quick Appoint');
        $mail->addAddress($recipientEmail, $recipientName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif;'>
                <h2>LGU Quick Appoint - Password Reset</h2>
                <p>Hi <strong>{$recipientName}</strong>,</p>
                <p>Click the button below to reset your password:</p>
                <p><a href='{$resetLink}' style='padding:10px 20px;background:#2980b9;color:#fff;text-decoration:none;border-radius:5px;'>Reset Password</a></p>
                <p>If you did not request this, please ignore this email.</p>
            </div>
        ";

        $mail->AltBody = "Hi {$recipientName},\n\nClick the link below to reset your password:\n{$resetLink}\n\nIf you did not request this, ignore this email.";

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Fallback: log the reset link locally for testing
        $logFile = __DIR__ . '/reset_link_log.txt';
        $logMessage = "[" . date('Y-m-d H:i:s') . "] Email: {$recipientEmail}, Name: {$recipientName}, Link: {$resetLink}, Error: {$mail->ErrorInfo}\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);

        return "Mailer Error: {$mail->ErrorInfo}. Reset link saved to reset_link_log.txt";
    }
}
