<?php
include_once __DIR__ . "/database.php";

class xl_data extends database {
    // read data
    public function __construct(){}

    // hàm thực hiện câu sql có lấy giá trị trả về
    function readitem($sql): array {
        $result = $this->connection_database()->query($sql);
        if ($result === false) {
            return [];
        }
        $danhsach = $result->fetchAll(PDO::FETCH_ASSOC);
        return $danhsach;
    }

    // execute data
    // hàm thực hiện câu sql không lấy giá trị trả về
    function execute_item($sql): void {
        // Tối ưu hóa: gọi trực tiếp connection của lớp cha thay vì tạo mới object
        $this->connection_database()->query($sql);
    }
}
?>
