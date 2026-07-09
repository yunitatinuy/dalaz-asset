<?php

class Complaint extends Model
{
    protected $table = 'complaint';

    // Simpan respons admin
    public function getAllPendingComplaints()
    {
        $sql = "SELECT 
                    r.id as return_id,
                    r.user_id,
                    r.equipment_id,
                    r.description as defect_description,
                    r.date as return_date,
                    r.time as return_time,
                    e.equipment_name,
                    e.asset_number,
                    u.full_name as user_name,
                    c.id as complaint_id,
                    c.control_no,
                    c.defect_cause,
                    c.treatment,
                    c.check_date,
                    c.check_status
                FROM `return` r
                LEFT JOIN equipment e ON r.equipment_id = e.id
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN complaint c ON r.id = c.return_id
                WHERE r.description IN ('cracked', 'lost', 'damaged')
                ORDER BY r.date DESC, r.time DESC";

        $this->db->query($sql);
        return $this->db->resultSet();
    }

    public function createTicketFromReturn($data)
    {
        // Cek duplikat
        $this->db->query("SELECT id FROM complaint WHERE return_id = :return_id");
        $this->db->bind(':return_id', $data['return_id']);
        if ($this->db->single()) return true;
        $query = "INSERT INTO complaint (return_id, user_id, equipment_id, defect_cause, photos, control_no, treatment, check_date, check_status) 
                VALUES (:return_id, :user_id, :equipment_id, :defect_cause, :photos, NULL, NULL, NULL, NULL)";

        $this->db->query($query);
        $this->db->bind(':return_id', $data['return_id']);
        $this->db->bind(':user_id', $data['user_id']);
        $this->db->bind(':equipment_id', $data['equipment_id']);
        $this->db->bind(':defect_cause', $data['defect_cause']);
        $this->db->bind(':photos', $data['photos'] ?? null);

        return $this->db->execute();
    }
    public function saveResponse($data)
    {
        $sql = "UPDATE complaint SET 
                    control_no = :control_no,
                    treatment = :treatment,
                    check_date = :check_date,
                    check_status = :check_status
                WHERE return_id = :return_id";

        $this->db->query($sql);
        $this->db->bind(':control_no', $data['control_no']);
        $this->db->bind(':treatment', $data['treatment']);
        $this->db->bind(':check_date', $data['check_date']);
        $this->db->bind(':check_status', $data['check_status']);
        $this->db->bind(':return_id', $data['return_id']);

        $result = $this->db->execute();

        // Jika Admin memilih 'disposal' (dimusnahkan) atau 'replace' (diganti baru)
        if ($result && (strtolower($data['check_status']) === 'disposal' || strtolower($data['check_status']) === 'replace')) {

            // Cari tahu ID Equipment-nya dari tabel return
            $this->db->query("SELECT equipment_id FROM `return` WHERE id = :return_id");
            $this->db->bind(':return_id', $data['return_id']);
            $ret = $this->db->single();

            if ($ret && $ret['equipment_id']) {
                // Pukul rata Quantity menjadi 0 di tabel equipment utama
                $this->db->query("UPDATE equipment SET quantity = 0 WHERE id = :eq_id");
                $this->db->bind(':eq_id', $ret['equipment_id']);
                $this->db->execute();
            }
        }

        return $result;
    }

    public function getDetailsByReturnId($return_id)
    {
        $sql = "SELECT 
                    r.id as return_id, r.date as return_date, r.time as return_time,
                    r.description as defect_description,
                    u.full_name as user_name, u.employee_no, u.position,
                    e.equipment_name, e.asset_number, e.serial_number, e.type as equipment_type, e.manufacturer,
                    l.location_name,
                    c.id as complaint_id, c.control_no, 
                    c.defect_cause, 
                    c.photos,
                    c.treatment, c.check_date, c.check_status
                FROM `return` r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN equipment e ON r.equipment_id = e.id
                LEFT JOIN location l ON e.location_id = l.id
                LEFT JOIN complaint c ON r.id = c.return_id
                WHERE r.id = :return_id";
        $this->db->query($sql);
        $this->db->bind(':return_id', $return_id);
        return $this->db->single();
    }

    // Mengecek apakah sebuah equipment memiliki komplain yang belum diproses Admin
    public function hasPendingComplaint($equipmentId)
    {
        $this->db->query("SELECT id FROM complaint WHERE equipment_id = :eq_id AND (check_status IS NULL OR check_status = '')");
        $this->db->bind(':eq_id', $equipmentId);
        $result = $this->db->single();

        return $result ? true : false;
    }
}
