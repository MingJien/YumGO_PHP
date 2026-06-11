<?php
declare(strict_types=1);

/**
 * YumGO - Controller quản lý tài khoản khách hàng.
 */

if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

require_once dirname(__DIR__) . '/models/User.php';

class UserAccountController
{
    private User $userModel;

    public function __construct(PDO $pdo)
    {
        $this->userModel = new User($pdo);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index(): void
    {
        requireUserLogin();

        $user = $this->currentUser();
        $profileErrors = $_SESSION['account_profile_errors'] ?? [];
        $passwordErrors = $_SESSION['account_password_errors'] ?? [];
        $profileSuccess = $_SESSION['account_profile_success'] ?? '';
        $passwordSuccess = $_SESSION['account_password_success'] ?? '';

        unset(
            $_SESSION['account_profile_errors'],
            $_SESSION['account_password_errors'],
            $_SESSION['account_profile_success'],
            $_SESSION['account_password_success']
        );

        $title = 'Tài khoản của tôi - YumGO';
        $metaDescription = 'Quản lý thông tin tài khoản khách hàng.';
        $canonicalUrl = BASE_URL . '/index.php?page=account';

        require_once dirname(__DIR__) . '/views/layouts/header.php';
        require_once dirname(__DIR__) . '/views/user/account.php';
        require_once dirname(__DIR__) . '/views/layouts/footer.php';
    }

    public function updateProfile(): void
    {
        requireUserLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/index.php?page=account');
        }

        $userId = (int)$_SESSION['user']['id'];
        $name = trim((string)($_POST['name'] ?? ''));
        $phone = $this->normalizePhone($_POST['phone'] ?? '');
        $address = trim((string)($_POST['address'] ?? ''));
        $errors = [];

        if ($name === '') {
            $errors[] = 'Vui lòng nhập họ tên.';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'Họ tên không được vượt quá 100 ký tự.';
        }

        if (!$this->isValidPhone($phone)) {
            $errors[] = 'Số điện thoại không hợp lệ.';
        } elseif ($this->userModel->phoneExistsForOtherUser($phone, $userId)) {
            $errors[] = 'Số điện thoại này đã được tài khoản khác sử dụng.';
        }

        if (mb_strlen($address) > 255) {
            $errors[] = 'Địa chỉ không được vượt quá 255 ký tự.';
        }

        if ($errors) {
            $_SESSION['account_profile_errors'] = $errors;
            redirect(BASE_URL . '/index.php?page=account');
        }

        $addressValue = $address !== '' ? $address : null;
        $this->userModel->updateProfile($userId, $name, $phone, $addressValue);

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['phone'] = $phone;
        $_SESSION['user']['address'] = $addressValue;
        $_SESSION['account_profile_success'] = 'Đã cập nhật thông tin tài khoản.';

        redirect(BASE_URL . '/index.php?page=account');
    }

    public function updatePassword(): void
    {
        requireUserLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/index.php?page=account');
        }

        $user = $this->currentUser();
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');
        $errors = [];

        if ($currentPassword === '') {
            $errors[] = 'Vui lòng nhập mật khẩu hiện tại.';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $errors[] = 'Mật khẩu hiện tại không đúng.';
        }

        if (strlen($newPassword) < 6) {
            $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Mật khẩu nhập lại không khớp.';
        }

        if ($errors) {
            $_SESSION['account_password_errors'] = $errors;
            redirect(BASE_URL . '/index.php?page=account');
        }

        $this->userModel->updatePassword((int)$user['id'], password_hash($newPassword, PASSWORD_DEFAULT));
        $_SESSION['account_password_success'] = 'Đã đổi mật khẩu thành công.';

        redirect(BASE_URL . '/index.php?page=account');
    }

    private function currentUser(): array
    {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $user = $this->userModel->findById($userId);

        if (!$user || (int)$user['is_active'] !== 1) {
            logout(BASE_URL . '/index.php?page=login');
        }

        return $user;
    }

    private function normalizePhone(mixed $phone): string
    {
        return preg_replace('/\s+/', '', trim((string)$phone)) ?? '';
    }

    private function isValidPhone(string $phone): bool
    {
        return preg_match('/^0[0-9]{9}$/', $phone) === 1;
    }
}
