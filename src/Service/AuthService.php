<?php

namespace App\Service;

use App\Repository\UserRepository;
use PDOException;
use RuntimeException;

final class AuthService
{
    public function __construct(private ?UserRepository $users = null)
    {
    }

    public function login(string $email, string $password): array
    {
        if ($this->users === null) {
            throw new RuntimeException('Auth service nije inicijaliziran.');
        }

        if ($email === '' || $password === '') {
            throw new RuntimeException('Email i lozinka su obavezni.');
        }

        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            throw new RuntimeException('Pogresan email ili lozinka.');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['is_admin'] = (int)($user['is_admin'] ?? 0);
        $_SESSION['full_name'] = (string)$user['full_name'];
        $_SESSION['email'] = (string)$user['email'];
        $csrfToken = issue_csrf_token();

        return [
            'id' => (int)$user['id'],
            'full_name' => (string)$user['full_name'],
            'email' => (string)$user['email'],
            'is_admin' => (int)($user['is_admin'] ?? 0),
            'csrf_token' => $csrfToken,
        ];
    }

    public function register(string $fullName, string $email, string $password, string $confirmPassword): array
    {
        if ($this->users === null) {
            throw new RuntimeException('Auth service nije inicijaliziran.');
        }

        if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
            throw new RuntimeException('Sva polja su obavezna.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Email nije ispravan.');
        }

        if ($password !== $confirmPassword) {
            throw new RuntimeException('Lozinke se ne podudaraju.');
        }

        if (strlen($password) < 6) {
            throw new RuntimeException('Lozinka mora imati barem 6 znakova.');
        }

        if ($this->users->existsByEmail($email)) {
            throw new RuntimeException('Korisnik s tim emailom vec postoji.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $userId = $this->users->create($fullName, $email, $hash);
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '23000') {
                throw new RuntimeException('Korisnik s tim emailom vec postoji.');
            }
            throw $e;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$userId;
        $_SESSION['is_admin'] = 0;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['email'] = $email;
        $csrfToken = issue_csrf_token();

        return [
            'id' => (int)$userId,
            'full_name' => $fullName,
            'email' => $email,
            'is_admin' => 0,
            'csrf_token' => $csrfToken,
        ];
    }

    public function logout(bool $validateCsrfIfLoggedIn = true): void
    {
        if ($validateCsrfIfLoggedIn && !empty($_SESSION['user_id'])) {
            require_valid_csrf_token();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool)$params['secure'],
                (bool)$params['httponly']
            );
        }

        session_destroy();
    }
}
