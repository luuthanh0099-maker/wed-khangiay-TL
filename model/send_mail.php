<?php
// Require các file của PHPMailer
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Hàm gửi email thông báo đơn hàng
 * 
 * @param string $to_email Email người nhận
 * @param string $to_name Tên người nhận
 * @param int $order_id Mã đơn hàng
 * @param array $order_items Danh sách các sản phẩm trong đơn hàng
 * @param float $total_price Tổng tiền đơn hàng
 * @param string $address Địa chỉ giao hàng
 */
function sendOrderConfirmationEmail($to_email, $to_name, $order_id, $order_items, $total_price, $address) {
    // ==========================================
    // ⚠️ CẤU HÌNH TÀI KHOẢN GỬI MAIL Ở ĐÂY ⚠️
    // ==========================================
    $smtp_email = 'luuthanh0099@gmail.com'; // Thay bằng Gmail của bạn
    $smtp_password = 'spiw djtz rlel qzsu'; // Thay bằng Mật khẩu ứng dụng 16 ký tự của Gmail
    
    // Đã cấu hình xong. Bắt đầu xử lý gửi email.
    $mail = new PHPMailer(true);

    try {
        // Cài đặt Server
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Tắt debug
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_email;
        $mail->Password   = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        // Người gửi & Người nhận
        $mail->setFrom($smtp_email, 'Website Bán Khăn Giấy - TL');
        $mail->addAddress($to_email, $to_name);

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = "Xác nhận đặt hàng thành công - Đơn hàng #$order_id";

        // Tạo giao diện bảng HTML cho email
        // Tìm Base URL để hiển thị hình ảnh chính xác
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        // Lấy thư mục gốc của website
        $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        // Xóa /trangchu nếu đang ở trong thư mục trangchu
        $base_dir = preg_replace('/\/trangchu\/?$/', '', $script_dir);
        $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $base_dir;

        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;'>
            <div style='background-color: #1b8a44; color: #fff; padding: 20px; text-align: center;'>
                <h2 style='margin: 0;'>Cảm ơn bạn đã đặt hàng!</h2>
                <p style='margin: 5px 0 0 0;'>Mã đơn hàng của bạn là <strong>#$order_id</strong></p>
            </div>
            
            <div style='padding: 20px;'>
                <p>Xin chào <strong>" . htmlspecialchars($to_name) . "</strong>,</p>
                <p>Chúng tôi đã nhận được yêu cầu đặt hàng của bạn và đang tiến hành xử lý. Dưới đây là thông tin chi tiết của đơn hàng:</p>
                
                <div style='background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>
                    <strong>Địa chỉ giao hàng:</strong><br>
                    " . htmlspecialchars($address) . "
                </div>

                <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                    <thead>
                        <tr style='background-color: #f2f2f2;'>
                            <th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Sản phẩm</th>
                            <th style='padding: 10px; text-align: center; border: 1px solid #ddd;'>SL</th>
                            <th style='padding: 10px; text-align: right; border: 1px solid #ddd;'>Đơn giá</th>
                        </tr>
                    </thead>
                    <tbody>";
        
        foreach ($order_items as $item) {
            $img_url = $base_url . '/' . ltrim($item['img'], '/.');
            $html .= "
                        <tr>
                            <td style='padding: 10px; border: 1px solid #ddd;'>
                                <div style='display: flex; align-items: center;'>
                                    <img src='$img_url' alt='".htmlspecialchars($item['name'])."' style='width: 50px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 10px; border: 1px solid #eee;'>
                                    <span>" . htmlspecialchars($item['name']) . "</span>
                                </div>
                            </td>
                            <td style='padding: 10px; text-align: center; border: 1px solid #ddd;'>" . $item['quantity'] . "</td>
                            <td style='padding: 10px; text-align: right; border: 1px solid #ddd; color: #ee4d2d;'>" . number_format($item['price'], 0, ',', '.') . "đ</td>
                        </tr>";
        }

        $html .= "
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan='2' style='padding: 10px; text-align: right; border: 1px solid #ddd; font-weight: bold;'>Tổng thanh toán:</td>
                            <td style='padding: 10px; text-align: right; border: 1px solid #ddd; font-weight: bold; color: #ee4d2d; font-size: 16px;'>" . number_format($total_price, 0, ',', '.') . "đ</td>
                        </tr>
                    </tfoot>
                </table>

                <p style='text-align: center; font-size: 14px; color: #666; margin-top: 30px;'>
                    Nếu có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi qua email này.<br>
                    Trân trọng,<br>
                    <strong>Đội ngũ TL</strong>
                </p>
            </div>
        </div>
        ";

        $mail->Body = $html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Ghi log lỗi nếu cần thiết: $mail->ErrorInfo
        return false;
    }
}
?>
