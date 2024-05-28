<?php
namespace com\icemalta\kahuna\model;

use \PDO;
use \JsonSerializable;
use com\icemalta\kahuna\model\DBConnect;


class Product implements JsonSerializable
{
    private static $db;
    private int $id = 0;
    private int $userId;
    private ?string $serial;
    private int|string $birth = 0;
    private ?string $name;
    private ?int $warrantyLength;

    public function __construct(int $userId, ?string $serial = null, int|string $birth = 0, ?string $name = null, ?int $warrantyLength = null, int $id = 0)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->serial = $serial;
        $this->birth = $birth; // Accept null or any value for birth
        $this->name = $name;
        $this->warrantyLength = $warrantyLength ?? 0; 
        $this->id = $id;
        self::$db = DBConnect::getInstance()->getConnection();
    }

    public static function save(Product $product): Product
    {
        if ($product->getId() === 0) {
            // New product (insert)
            $sql = 'INSERT INTO Product(userId, serial, name, warrantyLength) VALUES (:userId, :serial, :name, :warrantyLength)';
            $sth = self::$db->prepare($sql);
            $sth->bindValue(':userId', $product->getUserId(), PDO::PARAM_INT);
            $sth->bindValue(':serial', $product->getSerial());
            $sth->bindValue(':name', $product->getName());
            $sth->bindValue(':warrantyLength', $product->getWarrantyLength());
        } else {
            // Update product (update)
            $sql = 'UPDATE Product SET id = :id, serial = :serial, birth = :birth, name = :name, warrantyLength = :warrantyLength WHERE id = :id';
            $sth = self::$db->prepare($sql);
            $sth->bindValue(':id', $product->getId());
            $sth->bindValue(':serial', $product->getSerial());
            $sth->bindValue(':birth', $product->getBirth());
            $sth->bindValue(':name', $product->getName());
            $sth->bindValue(':warrantyLength', $product->getWarrantyLength());
        }
    
        $sth->execute();
    
        if ($sth->rowCount() > 0 && $product->getId() === 0) {
            $product->setId(self::$db->lastInsertId());
        }
    
        return $product;
    }

    public static function load(Product $product): array 
    {
        $sql = 'SELECT userId, serial, birth, name, warrantyLength, id FROM Product WHERE userId = :userId ORDER BY birth DESC';
        $sth = self::$db->prepare($sql);
        $sth->bindValue('userId', $product->getUserId());
        $sth->execute();
        $products = $sth->fetchAll(PDO::FETCH_FUNC, fn(...$fields) => new Product(...$fields));
        return $products;
    }

    public static function getAllProducts(): array
    {
        $sql = 'SELECT * FROM Product ORDER BY birth DESC';
        $sth = self::$db->prepare($sql);
        $sth->execute();
        
        $products = $sth->fetchAll(PDO::FETCH_FUNC, fn(...$fields) => new Product(...$fields));
        return $products;
    }

    public static function delete(Product $product): bool
    {
        $sql = 'DELETE FROM Product WHERE id = :id';
        $sth = self::$db->prepare($sql);
        $sth->bindValue('id', $product->getId());
        $sth->execute();
        return $sth->rowCount() > 0;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    //getters and setters
    public function getId(): int { return $this->id; }
    public function setId(int $id): self { $this->id = $id; return $this; }
    public function getBirth(): int|string { return $this->birth; }
    public function setBirth(int $birth): self { $this->birth = $birth; return $this; }
    public function getUserId(): int { return $this->userId; }
    public function setUserId(int $userId): self { $this->userId = $userId; return $this; }
    public function getSerial(): string { return $this->serial; }
    public function setSerial(string $serial): self { $this->serial = $serial; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getWarrantyLength(): int { return $this->warrantyLength; }
    public function setWarrantyLength(int $warrantyLength): self { $this->warrantyLength = $warrantyLength; return $this; }
}




