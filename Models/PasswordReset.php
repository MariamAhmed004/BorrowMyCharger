<?php
require_once 'Database.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class PasswordReset
{
    public static function emailExists($email)
    {
        $connection = Database::getInstance()->getDbConnection();
        $query = "SELECT user_id FROM Pro_User WHERE email = :email LIMIT 1";
        $statement = $connection->prepare($query);
        $statement->bindParam(':email', $email);
        $statement->execute();
        
        return $statement->fetch(PDO::FETCH_ASSOC);
    }
    
    public static function generateNumericToken()
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    public static function createToken($userId, $plainToken)
    {
        $connection = Database::getInstance()->getDbConnection();
        $hashedToken = hash('sha256', $plainToken);
        $expiresAt = date("Y-m-d H:i:s", strtotime("+30 minutes"));
        
        // First, delete any existing tokens for this user
        $deleteQuery = "DELETE FROM reset_tokens WHERE user_id = :user_id";
        $deleteStatement = $connection->prepare($deleteQuery);
        $deleteStatement->bindParam(':user_id', $userId);
        $deleteStatement->execute();
        
        // Now create the new token
        $query = "INSERT INTO reset_tokens (user_id, token, created_at, expires_at)
                  VALUES (:user_id, :token, NOW(), :expires_at)";
        $statement = $connection->prepare($query);
        $statement->bindParam(':user_id', $userId);
        $statement->bindParam(':token', $hashedToken);
        $statement->bindParam(':expires_at', $expiresAt);
        return $statement->execute();
    }
    
    public static function sendEmail($email, $token)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'borrowmycharger@gmail.com';
            $mail->Password = 'wfev cxol dfcw qbjd'; // Use app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('no-reply@yourdomain.com', 'No Reply');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Token';
            $mail->Body = 'Your password reset token is: <strong>' . $token . '</strong>';
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    public static function validateToken($plainToken)
    {
        $connection = Database::getInstance()->getDbConnection();
        $hashedToken = hash('sha256', $plainToken);
        $query = "SELECT user_id FROM reset_tokens 
                  WHERE token = :token AND expires_at > NOW() 
                  LIMIT 1";
        $statement = $connection->prepare($query);
        $statement->bindParam(':token', $hashedToken);
        $statement->execute();
        return $statement->fetch(PDO::FETCH_ASSOC);
    }
    
    public static function updatePassword($userId, $newPassword)
    {
        $connection = Database::getInstance()->getDbConnection();
        $query = "UPDATE Pro_User SET password = :password WHERE user_id = :user_id";
        $statement = $connection->prepare($query);
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $statement->bindParam(':password', $hashedPassword);
        $statement->bindParam(':user_id', $userId);
        return $statement->execute();
    }
    
    public static function deleteToken($plainToken)
    {
        $connection = Database::getInstance()->getDbConnection();
        $hashedToken = hash('sha256', $plainToken);
        $query = "DELETE FROM reset_tokens WHERE token = :token";
        $statement = $connection->prepare($query);
        $statement->bindParam(':token', $hashedToken);
        return $statement->execute();
    }
}