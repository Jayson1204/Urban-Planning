<?php

namespace App\Repositories;

class HouseholdRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function find($householdId)
    {
        $rows = $this->db->query(
            "SELECT * FROM households WHERE household_id = :id",
            ['id' => $householdId]
        );
        return $rows[0] ?? null;
    }

    public function search($term, $limit = 10)
    {
        $sql = "SELECT * FROM households
                WHERE household_number LIKE :term
                   OR barangay LIKE :term
                   OR street_address LIKE :term
                ORDER BY barangay, street_address
                LIMIT " . (int)$limit;
        return $this->db->query($sql, ['term' => '%' . $term . '%']);
    }

    public function create($data)
    {
        return $this->db->insert('households', $data);
    }

    public function update($householdId, $data)
    {
        return $this->db->update('households', $data, ['household_id' => $householdId]);
    }

    public function getMembers($householdId)
    {
        return $this->db->query(
            "SELECT resident_id, first_name, middle_name, last_name, relationship_to_head, status
             FROM residents WHERE household_id = :id ORDER BY
             FIELD(relationship_to_head, 'Head') DESC, first_name ASC",
            ['id' => $householdId]
        );
    }
}
