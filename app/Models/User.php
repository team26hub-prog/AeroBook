<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    public function profileForCustomer(int $userId): ?array
    {
        $statement = $this->db->prepare("SELECT full_name, email, phone, role, status, created_at FROM users WHERE id = :id AND role = 'customer' LIMIT 1");
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();
        return $user === false ? null : $user;
    }

    /** @return array{id:int, full_name:string, email:string, password_hash:string, role:string, status:string}|null */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, full_name, email, password_hash, role, status FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();
        return $user === false ? null : $user;
    }

    /** @return array{id:int, full_name:string, role:string, status:string}|null */
    public function findIdentityById(int $userId): ?array
    {
        $statement = $this->db->prepare('SELECT id, full_name, role, status FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();
        return $user === false ? null : $user;
    }

    /** @return array{id:int, full_name:string, role:string} */
    public function createCustomer(string $fullName, string $email, string $passwordHash): array
    {
        $statement = $this->db->prepare(
            "INSERT INTO users (full_name, email, password_hash, role, status) VALUES (:full_name, :email, :password_hash, 'customer', 'active')"
        );
        $statement->execute([
            'full_name' => $fullName,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        return [
            'id' => (int) $this->db->lastInsertId(),
            'full_name' => $fullName,
            'role' => 'customer',
        ];
    }

    public function updatePasswordHash(int $userId, string $passwordHash): void
    {
        $statement = $this->db->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $statement->execute(['password_hash' => $passwordHash, 'id' => $userId]);
    }
}
