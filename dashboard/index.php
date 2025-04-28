<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RC Attendance Records</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Poppins', Arial, sans-serif;
      background: linear-gradient(135deg, #f0f2f5, #d9e4f5);
      padding: 20px;
      color: #333;
      text-transform: uppercase;
    }

    h1 {
      text-align: center;
      margin-bottom: 20px;in
      color: #2c3e50;
      font-size: 2.5em;
    }

    .filter-container {
      max-width: 800px;
      margin: 0 auto 20px auto;
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: center;
      align-items: center;
      flex-wrap: wrap;
    }

    .filter-container input[type="date"],
    .filter-container button {
      padding: 10px;
      border: none;
      border-radius: 6px;
      font-size: 1em;
      margin: 5px;
    }

    .filter-container button {
      background-color: #3498db;
      color: white;
      cursor: pointer;
      transition: 0.3s;
    }

    .filter-container button:hover {
      background-color: #2980b9;
    }

    .table-container {
      overflow-x: auto;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      padding: 20px;
      margin-top: 20px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead {
      background-color: #3498db;
      color: white;
    }

    th, td {
      padding: 15px;
      text-align: left;
      border-bottom: 1px solid #ecf0f1;
    }

    tr.check-in { background-color: #eafaf1; }
    tr.check-out { background-color: #faeaea; }

    p { text-align: center; margin-top: 20px; }

    /* Toast Modal */
.photo-toast {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: rgba(0, 0, 0, 0.9);
  padding: 20px;
  border-radius: 12px;
  color: white;
  width: 90%;
  max-width: 400px;
  z-index: 10000;
  display: none;
  box-shadow: 0 8px 20px rgba(0,0,0,0.5);
}

.toast-content {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.loading-spinner {
  margin: 20px 0;
  border: 5px solid rgba(255, 255, 255, 0.2);
  border-top: 5px solid #3498db;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.slow-warning {
  color: #f1c40f;
  margin-top: 10px;
  font-size: 1em;
  animation: pulse 1s infinite alternate;
  display: none;
  text-align: center;
}

@keyframes pulse {
  from { opacity: 0.7; }
  to { opacity: 1; }
}

#photoImage {
  width: 100%;
  height: auto;
  border-radius: 8px;
  margin-top: 15px;
  display: none;
}

.button-group {
  margin-top: 15px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: center;
}

.button-group button {
  background-color: #e74c3c;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  font-size: 1em;
  cursor: pointer;
  color: white;
  transition: background-color 0.3s;
}

#retryButton {
  background-color: #f39c12;
}

#retryButton:hover {
  background-color: #e67e22;
}

#closeToast:hover {
  background-color: #c0392b;
}

/* Small screens (mobile) */
@media (max-width: 480px) {
  .photo-toast {
    width: 95%;
    padding: 15px;
  }

  .button-group {
    flex-direction: column;
    gap: 8px;
  }

  .button-group button {
    width: 100%;
  }
}

  </style>
</head>
<body>

<h1>RC Attendance Datewise Record</h1>

<div class="filter-container">
  <form method="GET" action="">
    <input type="date" style="border: 1px solid black;" name="date" value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d'); ?>">
    <button type="submit">Show Records</button>
  </form>
  <button style="background: red;" onclick="window.location.href='full-history.php'">View Full History</button>
</div>

<div class="table-container">
<?php
// --------------------------- PHP DATABASE CONNECTION ---------------------------

$servername = "localhost:3306";
$username = "rc-attendance-system";
$password = "rc-attendance-system";
$dbname = "rc-attendance-system";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $searchDate = date('M d Y', strtotime($selectedDate));

    $query = "SELECT ID, name, timestamp, action, ServerTimeStamp FROM data WHERE timestamp LIKE :date ORDER BY ServerTimeStamp DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute([':date' => "%$searchDate%"]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($records) > 0) {
        echo '<table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Timestamp</th>
                    <th>Action</th>
                    <th>Photo</th>
                  </tr>
                </thead>
                <tbody>';
        foreach ($records as $row) {
        $action = trim(strtolower($row['action']));
        $rowClass = ($action === 'check-in' || $action === 'check-in-permission') ? 'check-in' : 'check-out';
        $actionClass = ($action === 'check-in' || $action === 'check-in-permission') ? 'action-in' : 'action-out';

        $permissionMessage = "";
        switch($action)
        {
            case 'check-in-permission':
            case 'check-out-permission':
                $permissionMessage = " - (has permission)";
            break;
        }
        
        $serverTimestamp = $row['ServerTimeStamp'];
        // Add 11 hours 30 minutes to the server time for display
        $adjustedServerTimestamp = strtotime($serverTimestamp) + (12 * 60 * 60) + (30 * 60);    //to compensate 12 hours 30 mins difference in godaddy server
        //$adjustedServerTimestamp = date('Y-m-d H:i:s', $adjustedServerTimestamp);  // Format to readable timestamp
        
        // Format to readable format with shortened day, date, month, year, and time
        $adjustedServerTimestamp = date('D, d M Y - H:i:s', $adjustedServerTimestamp);  // 'D' for abbreviated day, 'M' for abbreviated month

        echo '<tr class="' . $rowClass . '">
                <td>'.htmlspecialchars($row['ID']).'</td>
                <td>'.htmlspecialchars($row['name']).'</td>
                <td>'.htmlspecialchars($adjustedServerTimestamp). $permissionMessage.'</td>
                <td class="'.$actionClass.'">'.htmlspecialchars($rowClass).'</td>
                <td><a href="#" class="view-photo-link" data-id="'.htmlspecialchars($row['ID']).'">View Photo</a></td>
              </tr>';
      }
        echo '</tbody></table>';
    } else {
        echo '<p>No attendance records found for the selected date.</p>';
    }

} catch(PDOException $e) {
    echo '<div style="color:red;">Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

?>
</div>

<!-- Photo Toast Modal -->
<div id="photoToast" class="photo-toast">
  <div class="toast-content">
    <div id="loadingSpinner" class="loading-spinner"></div>
    <p id="slowWarning" class="slow-warning">Loading is taking longer than usual...</p>
    <img id="photoImage" src="" alt="Attendance Photo">
    <div class="button-group">
      <button id="retryButton">Retry</button>
      <button id="closeToast">Close</button>
    </div>
  </div>
</div>


<script>
let photoLinks = document.querySelectorAll('.view-photo-link');
let photoToast = document.getElementById('photoToast');
let photoImage = document.getElementById('photoImage');
let loadingSpinner = document.getElementById('loadingSpinner');
let slowWarning = document.getElementById('slowWarning');
let retryButton = document.getElementById('retryButton');
let closeToast = document.getElementById('closeToast');

let currentRecordId = null;
let warningTimeout = null;

function fetchPhoto(recordId) {
  loadingSpinner.style.display = 'block';
  photoImage.style.display = 'none';
  slowWarning.style.display = 'none';
  retryButton.style.display = 'none';
  photoToast.style.display = 'block';

  warningTimeout = setTimeout(() => {
    slowWarning.style.display = 'block';
    retryButton.style.display = 'inline-block';
  }, 3000);

  fetch(`get_photo.php?id=${recordId}`)
    .then(response => response.json())
    .then(data => {
      if (data.photo) {
        photoImage.src = data.photo;
        photoImage.style.display = 'block';
        slowWarning.style.display = 'none';
        retryButton.style.display = 'none';
      } else {
        alert('No photo available for this record.');
      }
    })
    .catch(error => {
      console.error('Error fetching photo:', error);
      slowWarning.style.display = 'block';
      retryButton.style.display = 'inline-block';
    })
    .finally(() => {
      loadingSpinner.style.display = 'none';
      clearTimeout(warningTimeout);
    });
}

photoLinks.forEach(link => {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    currentRecordId = this.getAttribute('data-id');
    fetchPhoto(currentRecordId);
  });
});

retryButton.addEventListener('click', function() {
  if (currentRecordId) {
    fetchPhoto(currentRecordId);
  }
});

closeToast.addEventListener('click', function() {
  photoToast.style.display = 'none';
  clearTimeout(warningTimeout);
});

</script>

</body>
</html>
