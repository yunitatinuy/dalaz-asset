<?php

class User extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'users';
    }

    public function getAll()
    {
        $this->db->query("SELECT id, username, email, full_name, position, employee_no, role, profile_picture, qr_code FROM {$this->table} ORDER BY full_name ASC");
        return $this->db->resultSet();
    }

    public function getById($id)
    {
        $this->db->query("SELECT * FROM {$this->table} WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getUserByUsername($username)
    {
        if (empty($username)) return false;

        $this->db->query("SELECT * FROM {$this->table} WHERE username = :username");
        $this->db->bind(':username', $username);
        return $this->db->single();
    }

    public function getUserByEmployeeNo($employeeNo)
    {
        if (empty($employeeNo)) return false;

        $this->db->query("SELECT * FROM {$this->table} WHERE employee_no = :employee_no");
        $this->db->bind(':employee_no', $employeeNo);
        return $this->db->single();
    }

    public function getAdminEmails()
    {
        // Ambil email & nama dari semua user yang role-nya 'admin'
        $this->db->query("SELECT email, full_name FROM {$this->table} WHERE role = 'admin' AND email IS NOT NULL AND email != ''");
        return $this->db->resultSet();
    }

    public function usernameExists($username, $excludeId = null)
    {
        // Jangan cek username kosong/null
        if (empty($username)) return false;

        $sql = "SELECT id FROM {$this->table} WHERE username = :username AND username IS NOT NULL AND username != ''";
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
        }

        $this->db->query($sql);
        $this->db->bind(':username', $username);
        if ($excludeId) {
            $this->db->bind(':exclude_id', $excludeId);
        }

        return $this->db->single() !== false;
    }

    public function employeeNoExists($employeeNo, $excludeId = null)
    {
        if (empty($employeeNo)) return false;

        $sql = "SELECT id FROM {$this->table} WHERE employee_no = :employee_no";
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
        }

        $this->db->query($sql);
        $this->db->bind(':employee_no', $employeeNo);
        if ($excludeId) {
            $this->db->bind(':exclude_id', $excludeId);
        }

        return $this->db->single() !== false;
    }

    public function insert($data)
    {
        $this->db->query("
            INSERT INTO {$this->table} 
            (username, password, email, full_name, position, employee_no, role, profile_picture, qr_code) 
            VALUES 
            (:username, :password, :email, :full_name, :position, :employee_no, :role, :profile_picture, :qr_code)
        ");

        $this->db->bind(':username', $data['username'] ?? null);
        $this->db->bind(':password', $data['password'] ?? null);
        $this->db->bind(':email', $data['email'] ?? null);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':position', $data['position'] ?? '');
        $this->db->bind(':employee_no', $data['employee_no'] ?? '');
        $this->db->bind(':role', $data['role'] ?? 'user');
        $this->db->bind(':profile_picture', $data['profile_picture'] ?? null);
        $this->db->bind(':qr_code', $data['qr_code'] ?? null);

        return $this->db->execute();
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['username'])) {
            $fields[] = 'username = :username';
            $params[':username'] = empty($data['username']) ? null : $data['username'];
        }
        if (isset($data['email'])) {
            $fields[] = 'email = :email';
            $params[':email'] = empty($data['email']) ? null : $data['email'];
        }
        if (isset($data['password'])) {
            $fields[] = 'password = :password';
            $params[':password'] = empty($data['password']) ? null : $data['password'];
        }
        if (isset($data['full_name'])) {
            $fields[] = 'full_name = :full_name';
            $params[':full_name'] = $data['full_name'];
        }
        if (isset($data['position'])) {
            $fields[] = 'position = :position';
            $params[':position'] = $data['position'];
        }
        if (isset($data['employee_no'])) {
            $fields[] = 'employee_no = :employee_no';
            $params[':employee_no'] = $data['employee_no'];
        }
        if (isset($data['role'])) {
            $fields[] = 'role = :role';
            $params[':role'] = $data['role'];
        }
        if (isset($data['profile_picture'])) {
            $fields[] = 'profile_picture = :profile_picture';
            $params[':profile_picture'] = $data['profile_picture'];
        }
        if (isset($data['qr_code'])) {
            $fields[] = 'qr_code = :qr_code';
            $params[':qr_code'] = $data['qr_code'];
        }

        if (empty($fields)) return false;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->query($sql);

        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        return $this->db->execute();
    }

    public function delete($id)
    {
        $this->db->query("DELETE FROM {$this->table} WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function authenticate($username, $password)
    {
        if (empty($username) || empty($password)) return false;

        $user = $this->getUserByUsername($username);
        if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        return false;
    }

    // Untuk setting

    // update profil untuk nama dan posisi
    public function updateProfile($userId, $data)
    {
        $query = "UPDATE {$this->table} 
                SET full_name = :full_name, 
                    position = :position 
                WHERE id = :id";

        $this->db->query($query);
        $this->db->bind(':id', $userId);
        $this->db->bind(':full_name', $data['full_name']);
        $this->db->bind(':position', $data['position']);

        return $this->db->execute();
    }

    // update password
    public function updatePassword($userId, $newPassword)
    {
        $query = "UPDATE {$this->table} 
                SET password = :password 
                WHERE id = :id";

        $this->db->query($query);
        $this->db->bind(':id', $userId);
        $this->db->bind(':password', password_hash($newPassword, PASSWORD_DEFAULT));

        return $this->db->execute();
    }

    public function getUserByQRCode($qrCode)
    {
        $this->db->query("SELECT * FROM {$this->table} WHERE qr_code = :code OR employee_no = :code");
        $this->db->bind(':code', $qrCode);
        return $this->db->single();
    }
}
