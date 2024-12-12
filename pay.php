<?php
require_once "header.php";
session_start(); // 啟用 session

// 檢查登入狀態
if (!isset($_SESSION["account"])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';




// 接收查詢條件
$start_date = $_POST["start_date"] ?? "";
$end_date = $_POST["end_date"] ?? "";
$searchtxt = $_POST["searchtxt"] ?? "";
$order = $_POST["order"] ?? "";

// 防止 SQL 注入
$searchtxt = mysqli_real_escape_string($conn, $searchtxt);
$order = mysqli_real_escape_string($conn, $order);
$start_date = mysqli_real_escape_string($conn, $start_date);
$end_date = mysqli_real_escape_string($conn, $end_date);

// 處理日期範圍
if ($start_date && $end_date && $end_date < $start_date) {
    $temp_date = $end_date;
    $end_date = $start_date;
    $start_date = $temp_date;
}

// 設置查詢條件
$conditions = [];
if ($searchtxt) {
    $conditions[] = "(name LIKE '%$searchtxt%' OR stu_id LIKE '%$searchtxt%')";
}
if ($start_date && $end_date) {
    $conditions[] = "admission BETWEEN '$start_date' AND '$end_date'";
} elseif ($start_date) {
    $conditions[] = "admission >= '$start_date'";
} elseif ($end_date) {
    $conditions[] = "admission <= '$end_date'";
}
//
$condition_sql = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// 排序條件
$allowed_columns = ['name', 'stu_id', 'contact', 'admission', 'payment_status'];
$order_sql = ($order && in_array($order, $allowed_columns)) ? "ORDER BY $order" : "";

// 查詢會員資料
$sql = "SELECT * FROM member $condition_sql $order_sql";
$result = mysqli_query($conn, $sql);
if (!$result) {
    die("查詢失敗：" . mysqli_error($conn));
}

// 計算已繳費和未繳費的會員數
$paid_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM member WHERE payment_status = '已繳費'"));
$unpaid_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM member WHERE payment_status = '未繳費'"));
?>

<style>
  .search-container {
    display: flex;
    align-items: center; /* 垂直置中 */
    gap: 15px; /* 控制元素間的距離 */
    flex-wrap: wrap; /* 讓內容在較小螢幕時自動換行 */
  }

  .search-container select,
  .search-container input,
  .search-container button {
    height: 40px; /* 統一高度 */
    padding: 5px 10px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 5px; /* 圓角效果 */
  }

  .search-container select {
    width: 150px;
  }

  .search-container input[type="text"] {
    flex: 1; /* 讓文字搜尋框自動延展 */
  }

  .search-container input[type="date"] {
    width: 150px; /* 日期框固定寬度 */
  }

  .search-container button {
    width: 100px; /* 按鈕固定寬度 */
    background-color: #6c757d;
    color: white;
    border: none;
    cursor: pointer;
  }

  .search-container button:hover {
    background-color: #5a6268;
  }

 
  .search-container button {
    width: 55px; /* 調小按鈕寬度 */
    height: 40px; /* 調小按鈕高度 */
    font-size: 14px; /* 調小字體大小 */
    background-color: #6c757d;
    color: white;
    border: none;
    border-radius: 5px; /* 圓角 */
    cursor: pointer;
  }

  .search-container button:hover {
    background-color: #5a6268;
  }
</style>
 
</style>

<!-- 查詢表單 -->
<br>
<form action="pay.php" method="post" class="mb-4">
  <div class="search-container">
    <!-- 下拉選單 -->
    <select name="order">
      <option value="" <?= ($order == '') ? 'selected' : '' ?>>選擇排序欄位</option>
      <option value="name" <?= ($order == "name") ? "selected" : "" ?>>姓名</option>
      <option value="stu_id" <?= ($order == "stu_id") ? "selected" : "" ?>>學號</option>
      <option value="contact" <?= ($order == "contact") ? "selected" : "" ?>>電話</option>
      <option value="admission" <?= ($order == "admission") ? "selected" : "" ?>>繳費日期</option>
      <option value="payment_status" <?= ($order == "payment_status") ? "selected" : "" ?>>繳費狀態</option>
    </select>

    <!-- 搜尋文字輸入框 -->
    <input type="text" placeholder="搜尋名稱或學號" name="searchtxt" value="<?= htmlspecialchars($searchtxt) ?>">

    <!-- 日期範圍 -->
    <div class="row g-3">
    <div class="col-md-4">
    <label for="start_date" class="visually-hidden">開始日期</label>
    <input id="start_date" type="date" name="start_date" value="<?= $start_date ?>">

    <label for="end_date" class="visually-hidden">結束日期</label>
    <input id="end_date" type="date" name="end_date" value="<?= $end_date ?>">

    <!-- 搜尋按鈕 -->
    <button type="submit">搜尋</button>
  </div>
</form>
<br>

<!-- 顯示查詢結果 -->
<div class="container">
  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>姓名</th>
        <th>學號</th>
        <th>電話</th>
        <th>繳費日期</th>
        <th>繳費狀態</th>
        <th>操作</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = mysqli_fetch_assoc($result)) { ?>
        <tr id="row-<?= $row['stu_id'] ?>">
          <td><?= htmlspecialchars($row["name"]) ?></td>
          <td><?= htmlspecialchars($row["stu_id"]) ?></td>
          <td><?= htmlspecialchars($row["contact"]) ?></td>
          <td><?= htmlspecialchars($row["admission"]) ?></td>
          <td><?= htmlspecialchars($row["payment_status"]) ?></td>
          <td>
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal" 
                    data-id="<?= $row['stu_id'] ?>" 
                    data-status="<?= $row['payment_status'] ?>"
                    data-admission="<?= $row['admission'] ?>">
              修改
            </button>
           
        </tr>
      <?php } ?>
    </tbody>
  </table>
</div>

<!-- 模態框 -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">修改繳費狀態</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="updateForm" method="post">
          <input type="hidden" id="id" name="id">
          <div class="form-group">
            <label for="paidStatus">繳費狀態</label>
            <select id="paidStatus" class="form-select" name="payment_status">
              <option value="已繳費">已繳費</option>
              <option value="未繳費">未繳費</option>
            </select>
          </div>
          <div class="form-group mt-2">
            <label for="admissionDate">繳費日期</label>
            <input type="date" id="admissionDate" class="form-control" name="admission">
          </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
        <button type="submit" class="btn btn-primary">確認修改</button>
      </div>
      </form>
    </div>
  </div>
</div>

<script>
  var editModal = document.getElementById('editModal');
  editModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget; 
    var id = button.getAttribute('data-id');
    var status = button.getAttribute('data-status');
    var admission = button.getAttribute('data-admission');

    var modalId = editModal.querySelector('#id');
    var modalStatus = editModal.querySelector('#paidStatus');
    var modalAdmission = editModal.querySelector('#admissionDate');

    modalId.value = id;
    modalStatus.value = status;
    modalAdmission.value = admission;
  });

  document.getElementById('updateForm').addEventListener('submit', function(event) {
    event.preventDefault(); 

    var formData = new FormData(this);

    fetch('update.pay.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        var row = document.getElementById('row-' + formData.get('id'));
        // row.querySelector('.payment-status').innerHTML = (formData.get('payment_status') == '已繳費') ? '已繳費' : '未繳費';
        var modal = bootstrap.Modal.getInstance(editModal);
        modal.hide();
        
      } else {
        alert("更新失敗：" + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert("發生錯誤，請稍後再試");
    });
  });


  document.getElementById('updateForm').addEventListener('submit', function(event) {
    event.preventDefault(); 

    var formData = new FormData(this);

    fetch('update.pay.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 隱藏模態框
            var modal = bootstrap.Modal.getInstance(editModal);
            modal.hide();
            window.location.reload();
        } else {
            alert("更新失敗：" + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("發生錯誤，請稍後再試");
    });
});


</script>

<!-- 圖表 -->
<br>
<br>
<canvas id="feeChart" width="200" height="200" style="max-width: 300px; max-height: 300px;display: block; margin: 0 auto;"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // 從 PHP 端接收會費數據
  const paid = <?php echo $paid_count; ?>;
  const unpaid = <?php echo $unpaid_count; ?>;

  // 設置圓餅圖的資料
  const data = {
    labels: ['已繳會費', '未繳會費'],
    datasets: [{
      data: [paid, unpaid],
      backgroundColor: ['#36A2EB', '#FF6384'],
      hoverBackgroundColor: ['#2196F3', '#FF3D56']
    }]
  };

  // 設置圖表選項
  const options = {
    responsive: true,
    plugins: {
      legend: {
        position: 'top',
      },
      tooltip: {
        callbacks: {
          label: function(tooltipItem) {
            let label = tooltipItem.label || '';
            if (label) {
              label += ': ' + tooltipItem.raw + '人';
            }
            return label;
          }
        }
      },
      datalabels: {
        color: '#fff', // 標籤顏色
        font: {
          weight: 'bold',
          size: 14
        },
        formatter: (value, context) => {
          const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
          const percentage = ((value / total) * 100).toFixed(1);
          return value + '人 (' + percentage + '%)';
        }
      }
    }
  };

  // 渲染圓餅圖
  const ctx = document.getElementById('feeChart').getContext('2d');
  new Chart(ctx, {
    type: 'pie',
    data: data,
    options: options
  });
</script>

</div>

<script>
// JavaScript 代碼
document.addEventListener('DOMContentLoaded', function() {
  // 從 PHP 獲取已繳費和未繳費人數
  const paid = <?php echo $paid_count; ?>;
  const unpaid = <?php echo $unpaid_count; ?>;

  // 使用 Chart.js 繪製餅狀圖
  const ctx = document.getElementById('feeChart').getContext('2d');
  new Chart(ctx, {
    type: 'pie',
    data: {
      labels: ['已繳會費', '未繳會費'],
      datasets: [{
        data: [paid, unpaid],
        backgroundColor: ['#36A2EB', '#FF6384'],
        hoverBackgroundColor: ['#2196F3', '#FF3D56']
      }]
    },
    options: {
      // 圖表選項配置
    }
  });
});


// JavaScript 代碼
document.getElementById('updateForm').addEventListener('submit', function(event) {
  event.preventDefault(); 

  var formData = new FormData(this);

  fetch('update.pay.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // 更新圖表數據
      // updateFeeChart(data.paid, data.unpaid);
    } else {
      alert("更新失敗：" + data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert("發生錯誤，請稍後再試");
  });
});

function updateFeeChart(paid, unpaid) {
  // 使用新的數據更新圖表
  const ctx = document.getElementById('feeChart').getContext('2d');
  new Chart(ctx, {
    type: 'pie',
    data: {
      labels: ['已繳會費', '未繳會費'],
      datasets: [{
        data: [paid, unpaid],
        backgroundColor: ['#36A2EB', '#FF6384'],
        hoverBackgroundColor: ['#2196F3', '#FF3D56']
      }]
    },
    options: {
      // 圖表選項配置
    }
  });
}
</script>

<?php
mysqli_free_result($result);
mysqli_close($conn);

require_once "footer.php";
?>
