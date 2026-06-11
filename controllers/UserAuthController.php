<?php
declare(strict_types=1);

/**
 * YumGO - Controller điều phối đăng nhập và đăng ký khách hàng.
 */

if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

class UserAuthController
{
    public function login(): void
    {
        $redirectPage = $this->getSafeRedirectPage();
        $this->redirectLoggedInUser($redirectPage);

        $errors = [];
        $old = [
            'phone' => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old['phone'] = $this->normalizePhone($_POST['phone'] ?? '');
            $password = (string)($_POST['password'] ?? '');

            if (!$this->isValidPhone($old['phone'])) {
                $errors[] = 'Số điện thoại không hợp lệ.';
            }

            if ($password === '') {
                $errors[] = 'Vui lòng nhập mật khẩu.';
            }

            if (!$errors && loginUser($old['phone'], $password)) {
                redirect($this->redirectUrl($redirectPage));
            }

            if (!$errors) {
                $errors[] = 'Số điện thoại hoặc mật khẩu không đúng.';
            }
        }

        $title = 'Đăng nhập tài khoản - YumGO';
        $metaDescription = 'Đăng nhập tài khoản khách hàng bằng số điện thoại và mật khẩu.';
        $canonicalUrl = BASE_URL . '/index.php?page=login';

        require_once dirname(__DIR__) . '/views/layouts/header.php';
        require_once dirname(__DIR__) . '/views/user/login.php';
        require_once dirname(__DIR__) . '/views/layouts/footer.php';
    }

    public function register(): void
    {
        $redirectPage = $this->getSafeRedirectPage();
        $this->redirectLoggedInUser($redirectPage);

        $errors = [];
        $old = [
            'name' => '',
            'phone' => '',
            'address' => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old['name'] = trim((string)($_POST['name'] ?? ''));
            $old['phone'] = $this->normalizePhone($_POST['phone'] ?? '');
            $old['address'] = trim((string)($_POST['address'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');

            if ($old['name'] === '') {
                $errors[] = 'Vui lòng nhập họ tên.';
            } elseif (mb_strlen($old['name']) > 100) {
                $errors[] = 'Họ tên không được vượt quá 100 ký tự.';
            }

            if (!$this->isValidPhone($old['phone'])) {
                $errors[] = 'Số điện thoại không hợp lệ.';
            }

            if (strlen($password) < 6) {
                $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
            }

            if ($password !== $confirmPassword) {
                $errors[] = 'Mật khẩu nhập lại không khớp.';
            }

            if (mb_strlen($old['address']) > 255) {
                $errors[] = 'Địa chỉ không được vượt quá 255 ký tự.';
            }

            if (!$errors) {
                $address = $old['address'] !== '' ? $old['address'] : null;

                try {
                    if (registerUser($old['name'], $old['phone'], $password, $address)) {
                        redirect($this->redirectUrl($redirectPage));
                    }
                    $errors[] = 'Số điện thoại này đã được đăng ký.';
                } catch (PDOException $e) {
                    $errors[] = 'Không thể tạo tài khoản lúc này. Vui lòng thử lại.';
                }
            }
        }

        $title = 'Đăng ký tài khoản - YumGO';
        $metaDescription = 'Đăng ký tài khoản khách hàng để đặt món nhanh hơn.';
        $canonicalUrl = BASE_URL . '/index.php?page=register';

        require_once dirname(__DIR__) . '/views/layouts/header.php';
        require_once dirname(__DIR__) . '/views/user/register.php';
        require_once dirname(__DIR__) . '/views/layouts/footer.php';
    }

    public function logout(): void
    {
        logout(BASE_URL . '/index.php?page=home');
    }

    private function normalizePhone(mixed $phone): string
    {
        return preg_replace('/\s+/', '', trim((string)$phone)) ?? '';
    }

    private function isValidPhone(string $phone): bool
    {
        return preg_match('/^0[0-9]{9}$/', $phone) === 1;
    }

    private function getSafeRedirectPage(): string
    {
        $redirect = trim((string)($_POST['redirect'] ?? $_GET['redirect'] ?? ''));
        $allowed = ['checkout', 'cart', 'account'];

        return in_array($redirect, $allowed, true) ? $redirect : 'home';
    }

    private function redirectUrl(string $page): string
    {
        return BASE_URL . '/index.php?page=' . urlencode($page);
    }

    private function redirectLoggedInUser(string $redirectPage): void
    {
        startSession();

        if (!isset($_SESSION['role'])) {
            return;
        }

        if ($_SESSION['role'] === 'admin') {
            redirect(ADMIN_DASHBOARD_URL);
        }

        if ($_SESSION['role'] === 'shipper') {
            redirect(SHIPPER_DASHBOARD_URL);
        }

        if ($_SESSION['role'] === 'user') {
            redirect($this->redirectUrl($redirectPage));
        }

        redirect(BASE_URL);
    }
}
