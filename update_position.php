<?php
session_start();
require_once 'db.php';  // 請確認這裡的 db.php 正確連接資料庫

// 確認是否有學生ID和幹部職位
if (!isset($_POST['stu_id']) || empty($_POST['stu_id'])) {
    $_SESSION['error'] = '缺少學生資料';
    header("Location: 成員活躍度追蹤.php");
    exit();
}

$stu_id = $_POST['stu_id'];
$positions = isset($_POST['positions']) ? mysqli_real_escape_string($conn, $_POST['positions']) : null;  // 防止SQL注入，並處理未設定的情況

// $positions = mysqli_real_escape_string($conn, $_POST['positions']);  // 防止SQL注入


// 如果 positions 為空，則刪除該學生的幹部資料
if ($positions === null || $positions === '') {
    $sql_delete = "DELETE FROM positions WHERE stu_id = '$stu_id'";
    if (mysqli_query($conn, $sql_delete)) {
        $_SESSION['message'] = '幹部職位已刪除';
    } else {
        $_SESSION['error'] = '刪除幹部職位失敗：' . mysqli_error($conn);
    }

} else {

// 檢查是否已經有該學生的幹部資料
$sql_check = "SELECT * FROM positions WHERE stu_id = '$stu_id'";
$result_check = mysqli_query($conn, $sql_check);

 if (mysqli_num_rows($result_check) > 0) {
        // 如果有，執行更新
        $sql_update = "UPDATE positions SET position_name = '$positions' WHERE stu_id = '$stu_id'";
        if (mysqli_query($conn, $sql_update)) {
            $_SESSION['message'] = '幹部職位更新成功';
        } else {
            $_SESSION['error'] = '更新失敗：' . mysqli_error($conn);
        }
    } else {
        // 如果沒有，插入一筆新的幹部資料
        $sql_insert = "INSERT INTO positions (stu_id, position_name) VALUES ('$stu_id', '$positions')";
        if (mysqli_query($conn, $sql_insert)) {
            $_SESSION['message'] = '幹部職位新增成功';
        } else {
            $_SESSION['error'] = '新增失敗：' . mysqli_error($conn);
    }
}
}
// 回到原來的頁面
header("Location: 成員活躍度追蹤.php");  
exit();
?>
