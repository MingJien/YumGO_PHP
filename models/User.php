<?php
declare(strict_types=1);

if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

class User
{
    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function findByPhone(string $phone): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, phone, password, address, avatar, is_active
             FROM users
             WHERE phone = :phone
             LIMIT 1'
        );
        $stmt->execute(['phone' => $phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function activeByPhone(string $phone): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, phone, password, address, avatar
             FROM users
             WHERE phone = :phone AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute(['phone' => $phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, phone, password, address, avatar, is_active, created_at, updated_at
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function phoneExists(string $phone): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE phone = :phone');
        $stmt->execute(['phone' => $phone]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function phoneExistsForOtherUser(string $phone, int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE phone = :phone AND id <> :id');
        $stmt->execute([
            'phone' => $phone,
            'id' => $userId,
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(string $name, string $phone, string $passwordHash, ?string $address = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, phone, password, address)
             VALUES (:name, :phone, :password, :address)'
        );
        $stmt->execute([
            'name' => $name,
            'phone' => $phone,
            'password' => $passwordHash,
            'address' => $address,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function updateProfile(int $id, string $name, string $phone, ?string $address = null): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET name = :name, phone = :phone, address = :address
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET password = :password
             WHERE id = :id'
        );

        return $stmt->execute([
            'id' => $id,
            'password' => $passwordHash,
        ]);
    }
}
