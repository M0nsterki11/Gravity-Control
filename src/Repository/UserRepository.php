<?php

namespace App\Repository;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    // Vraca osnovne podatke svih korisnika za admin forme i tablice.
    public function listAllBasic(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, full_name, email, is_admin FROM users ORDER BY full_name ASC, email ASC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Vraca korisnika po emailu ili null ako ne postoji.
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, full_name, email, password_hash, is_admin FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    // Brza provjera postoji li email u sustavu.
    public function existsByEmail(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

        return (bool)$stmt->fetch();
    }

    // Provjera postoji li korisnik po ID-u.
    public function existsById(int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);

        return (bool)$stmt->fetch();
    }

    // Sprema novog korisnika i vraca njegov ID.
    public function create(string $fullName, string $email, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$fullName, $email, $passwordHash]);

        return (int)$this->pdo->lastInsertId();
    }
}

