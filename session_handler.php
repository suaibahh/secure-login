<?php
// session_handler.php

class MySQLSessionHandler implements SessionHandlerInterface {
    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    public function open($savePath, $sessionName): bool {
        // Koneksi sudah diberikan di constructor
        return $this->conn != null;
    }

    public function close(): bool {
        // Biarkan koneksi database tetap terbuka untuk sisa script
        return true;
    }

    public function read($session_id): string {
        $stmt = $this->conn->prepare("SELECT data FROM sessions WHERE id = ? LIMIT 1");
        $stmt->bind_param("s", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['data'] ?? ''; // Kembalikan data atau string kosong
        }
        
        $stmt->close();
        return ''; // Tidak ada data
    }

    public function write($session_id, $session_data): bool {
        $timestamp = time();
        
        // Gunakan REPLACE INTO (MySQL specific) atau INSERT ... ON DUPLICATE KEY UPDATE
        // REPLACE INTO lebih sederhana di sini
        $stmt = $this->conn->prepare("REPLACE INTO sessions (id, data, last_accessed) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $session_id, $session_data, $timestamp);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    public function destroy($session_id): bool {
        $stmt = $this->conn->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->bind_param("s", $session_id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    public function gc($maxlifetime): int {
        // Hapus session lama
        $past = time() - $maxlifetime;
        
        $stmt = $this->conn->prepare("DELETE FROM sessions WHERE last_accessed < ?");
        $stmt->bind_param("i", $past);
        $stmt->execute();
        
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        return $affected_rows; // Kembalikan jumlah baris yang dihapus
    }
}
?>