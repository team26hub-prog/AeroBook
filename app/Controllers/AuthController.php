<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuthService;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\User;
use PDOException;

final class AuthController extends Controller
{
    public function showRegister(): void
    {
        $this->redirectAuthenticatedUser();
        $this->view('auth/register', $this->formData());
    }

    public function register(): void
    {
        $this->requireValidCsrf();

        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
        $errors = [];

        if ($fullName === '' || $this->characterLength($fullName) > 150) {
            $errors[] = 'Enter your name using no more than 150 characters.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors[] = 'Enter a valid email address.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'Your password must be between 8 and 72 bytes.';
        }
        if ($password !== $passwordConfirmation) {
            $errors[] = 'The password confirmation does not match.';
        }

        $users = new User();
        if ($errors === [] && $users->findByEmail($email) !== null) {
            $errors[] = 'An account with that email address already exists.';
        }

        if ($errors !== []) {
            $this->storeFormErrors($errors, ['full_name' => $fullName, 'email' => $email]);
            $this->redirect('/register');
        }

        try {
            $user = $users->createCustomer($fullName, $email, password_hash($password, PASSWORD_DEFAULT));
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
            $this->storeFormErrors(['An account with that email address already exists.'], ['full_name' => $fullName, 'email' => $email]);
            $this->redirect('/register');
        }

        Auth::login($user);
        Session::flash('success', 'Your customer account is ready.');
        $this->redirect('/account');
    }

    public function showLogin(): void
    {
        $this->redirectAuthenticatedUser();
        $this->view('auth/login', $this->formData());
    }

    public function login(): void
    {
        $this->requireValidCsrf();

        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if ((new AuthService())->authenticate($email, $password, 'customer')) {
            $this->redirect('/account');
        }

        $this->storeFormErrors(['Email or password is incorrect, or this account is unavailable.'], ['email' => $email]);
        $this->redirect('/login');
    }

    public function logout(): void
    {
        $this->requireValidCsrf();
        Auth::logout();
        Session::flash('success', 'You have been signed out.');
        $this->redirect('/login');
    }

    /** @return array{errors:array, old:array, success:?string, csrf:string} */
    private function formData(): array
    {
        return [
            'errors' => Session::pullFlash('errors', []),
            'old' => Session::pullFlash('old', []),
            'success' => Session::pullFlash('success'),
            'csrf' => Csrf::token(),
        ];
    }

    private function storeFormErrors(array $errors, array $old): void
    {
        Session::flash('errors', $errors);
        Session::flash('old', $old);
    }

    private function characterLength(string $value): int
    {
        $count = preg_match_all('/./us', $value);
        return $count === false ? PHP_INT_MAX : $count;
    }
}
