<?php

namespace App\Repositories;

class CitizenAccountRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function find($citizenAccountId)
    {
        $rows = $this->db->query(
            "SELECT * FROM citizen_accounts WHERE citizen_account_id = :id",
            ['id' => $citizenAccountId]
        );
        return $rows[0] ?? null;
    }

    public function findByEmail($email)
    {
        $rows = $this->db->query(
            "SELECT * FROM citizen_accounts WHERE email = :email",
            ['email' => $email]
        );
        return $rows[0] ?? null;
    }

    public function findByResidentId($residentId)
    {
        $rows = $this->db->query(
            "SELECT * FROM citizen_accounts WHERE resident_id = :resident_id",
            ['resident_id' => $residentId]
        );
        return $rows[0] ?? null;
    }

    public function findByToken($token)
    {
        $rows = $this->db->query(
            "SELECT * FROM citizen_accounts WHERE password_set_token = :token",
            ['token' => $token]
        );
        return $rows[0] ?? null;
    }

    public function create($data)
    {
        return $this->db->insert('citizen_accounts', $data);
    }

    public function setPassword($citizenAccountId, $passwordHash)
    {
        return $this->db->update('citizen_accounts', [
            'password_hash' => $passwordHash,
            'status' => 'Active',
            'password_set_token' => null,
            'password_set_token_expires_at' => null,
        ], ['citizen_account_id' => $citizenAccountId]);
    }

    public function setToken($citizenAccountId, $token, $expiresAt)
    {
        return $this->db->update('citizen_accounts', [
            'password_set_token' => $token,
            'password_set_token_expires_at' => $expiresAt,
        ], ['citizen_account_id' => $citizenAccountId]);
    }
}
