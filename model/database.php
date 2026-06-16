<?php
class database {
    private $servername = "localhost";
    private $username = "root";
    private $password = "";
    private $databasename = "wed_khangiay";
    protected $conn = null;

    function connection_database(): PDO {
        try {
            // Thiết lập charset utf8mb4 để hỗ trợ tiếng Việt giống như config hiện tại
            $conn = new PDO("mysql:host=$this->servername;dbname=$this->databasename;charset=utf8mb4", 
                            $this->username, $this->password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            throw $e;
        }
        return $conn;
    }
}
?>
