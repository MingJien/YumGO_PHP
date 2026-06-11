<?php
declare(strict_types=1);

if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

class UserCart
{
    private PDO $db;
    private static bool $schemaChecked = false;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
        try {
            $this->ensureSchema();
        } catch (Throwable $exception) {
            error_log('Could not ensure user_carts schema: ' . $exception->getMessage());
        }
    }

    private function ensureSchema(): void
    {
        if (self::$schemaChecked) {
            return;
        }

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS user_carts (
              user_id INT UNSIGNED NOT NULL,
              food_id INT UNSIGNED NOT NULL,
              quantity INT UNSIGNED NOT NULL,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (user_id, food_id),
              CONSTRAINT fk_user_carts_user
                FOREIGN KEY (user_id) REFERENCES users(id)
                ON UPDATE CASCADE ON DELETE CASCADE,
              CONSTRAINT fk_user_carts_food
                FOREIGN KEY (food_id) REFERENCES foods(id)
                ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB"
        );

        self::$schemaChecked = true;
    }

    public function getCart(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT food_id, quantity
             FROM user_carts
             WHERE user_id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);

        $cart = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $foodId = (int)$row['food_id'];
            $quantity = (int)$row['quantity'];

            if ($foodId > 0 && $quantity > 0) {
                $cart[$foodId] = [
                    'food_id' => $foodId,
                    'quantity' => $quantity,
                ];
            }
        }

        return $cart;
    }

    public function replaceCart(int $userId, array $cart): void
    {
        $this->db->beginTransaction();

        try {
            $deleteStmt = $this->db->prepare('DELETE FROM user_carts WHERE user_id = :user_id');
            $deleteStmt->execute(['user_id' => $userId]);

            $insertStmt = $this->db->prepare(
                'INSERT INTO user_carts (user_id, food_id, quantity)
                 VALUES (:user_id, :food_id, :quantity)'
            );

            foreach ($cart as $foodId => $item) {
                $foodId = (int)($item['food_id'] ?? $foodId);
                $quantity = (int)($item['quantity'] ?? 0);

                if ($foodId <= 0 || $quantity <= 0) {
                    continue;
                }

                $insertStmt->execute([
                    'user_id' => $userId,
                    'food_id' => $foodId,
                    'quantity' => $quantity,
                ]);
            }

            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
