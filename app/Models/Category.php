<?php
namespace App\Models;

use App\Core\Database;

class Category
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findByUserId($userId, $type = null, $activeOnly = true)
    {
        $sql = "SELECT c.* FROM categories c WHERE 1=1";
        $params = [];

        // Categories are global (not user-specific) but we can filter by type
        if ($type !== null) {
            $sql .= " AND c.type = :type";
            $params['type'] = $type;
        }

        if ($activeOnly) {
            $sql .= " AND c.is_active = TRUE";
        }

        $sql .= " ORDER BY c.type, c.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getHierarchicalCategories($type = null)
    {
        $sql = "SELECT c.*, p.name as parent_name 
                FROM categories c 
                LEFT JOIN categories p ON c.parent_id = p.id 
                WHERE 1=1";
        $params = [];

        if ($type !== null) {
            $sql .= " AND c.type = :type";
            $params['type'] = $type;
        }

        $sql .= " AND c.is_active = TRUE 
                  ORDER BY c.type, COALESCE(p.name, ''), c.name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        // Build hierarchical structure
        $hierarchy = [];
        foreach ($results as $category) {
            if ($category['parent_id'] === null) {
                // Parent category
                $hierarchy[$category['id']] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'type' => $category['type'],
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                    'children' => []
                ];
            } else {
                // Sub-category
                if (isset($hierarchy[$category['parent_id']])) {
                    $hierarchy[$category['parent_id']]['children'][] = [
                        'id' => $category['id'],
                        'name' => $category['name'],
                        'type' => $category['type'],
                        'icon' => $category['icon'],
                        'color' => $category['color']
                    ];
                }
            }
        }

        return array_values($hierarchy);
    }

    public function create($name, $type, $parentId = null, $icon = null, $color = null)
    {
        // Check if category with same name, type, and parent already exists
        $stmt = $this->db->prepare("
            SELECT id FROM categories 
            WHERE name = :name AND type = :type AND 
                  (parent_id IS NULL AND :parentId IS NULL OR parent_id = :parentId)
            LIMIT 1
        ");
        $stmt->execute([
            'name' => $name,
            'type' => $type,
            'parentId' => $parentId
        ]);

        if ($stmt->fetch()) {
            throw new \Exception('Category with this name already exists');
        }

        $stmt = $this->db->prepare("
            INSERT INTO categories (name, type, parent_id, icon, color)
            VALUES (:name, :type, :parent_id, :icon, :color)
        ");

        $stmt->execute([
            'name' => $name,
            'type' => $type,
            'parent_id' => $parentId,
            'icon' => $icon,
            'color' => $color
        ]);

        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $allowedFields = ['name', 'icon', 'color', 'is_active'];
        $updates = [];
        $params = ['id' => $id];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE categories SET " . implode(', ', $updates) . " WHERE id = :id");
        return $stmt->execute($params);
    }

    public function delete($id)
    {
        // Check if category has subcategories
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = :id");
        $stmt->execute(['id' => $id]);
        $childCount = $stmt->fetchColumn();

        if ($childCount > 0) {
            throw new \Exception('Cannot delete category with subcategories');
        }

        // Check if category is used in transactions
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM transactions WHERE category_id = :id");
        $stmt->execute(['id' => $id]);
        $transactionCount = $stmt->fetchColumn();

        if ($transactionCount > 0) {
            throw new \Exception('Cannot delete category that is used in transactions');
        }

        // Soft delete
        $stmt = $this->db->prepare("UPDATE categories SET is_active = FALSE WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getRootCategories($type = null)
    {
        $sql = "SELECT * FROM categories WHERE parent_id IS NULL AND is_active = TRUE";
        $params = [];

        if ($type !== null) {
            $sql .= " AND type = :type";
            $params['type'] = $type;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}