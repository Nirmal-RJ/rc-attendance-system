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
      background: linear-gradient(135deg, #20232a, #2f3542);
      padding: 20px;
      color: #333;
      text-transform: uppercase;
    }

    h1 {
      text-align: center;
      margin-bottom: 20px;
      color: white;
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

    .filter-container input[type="text"] {
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1em;
      margin-right: 10px;
      flex: 1 1 auto;
      min-width: 200px;
    }

    .filter-container button {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      background-color: #3498db;
      color: white;
      font-size: 1em;
      cursor: pointer;
      margin: 5px;
      transition: 0.3s;
    }

    .filter-container button#resetFilter {
      background-color: #e67e22;
    }

    .filter-container button:hover {
      opacity: 0.8;
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
      font-size: 1em;
    }

    tr.check-in {
      background-color: #eafaf1;
    }

    tr.check-out {
      background-color: #faeaea;
    }

    p {
      text-align: center;
      font-size: 1.2em;
      margin-top: 20px;
    }

    /* Photo Toast Styles */
    .photo-toast {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%); /* Perfectly center the modal */
      background-color: rgba(0, 0, 0, 0.8);
      color: white;
      padding: 20px;
      border-radius: 8px;
      max-width: 500px;
      z-index: 1000;
      display: none;
      text-align: center;
      transition: all 0.3s ease-in-out;
      opacity: 0; /* Start with hidden */
      animation: fadeIn 0.3s forwards; /* Add fadeIn animation */
    }

    /* Fade-in Animation */
    @keyframes fadeIn {
      0% { opacity: 0; }
      100% { opacity: 1; }
    }

    .photo-toast img {
      max-width: 100%;
      height: auto;
      border-radius: 8px;
    }

    .photo-toast button {
      background-color: #e74c3c;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1em;
      margin-top: 10px;
      display: inline-block;
    }

    .photo-toast button:hover {
      background-color: #c0392b;
    }

    .loading-spinner {
      display: none;
      border: 4px solid rgba(0, 0, 0, 0.1);
      border-top: 4px solid #3498db;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
      margin: 0 auto;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
      .filter-container {
        flex-direction: column;
        align-items: stretch;
      }

      .filter-container input[type="text"], 
      .filter-container button {
        width: 100%;
        margin-bottom: 10px;
      }

      table, th, td {
        font-size: 0.9em;
      }
    }
  </style>
</head>

<body>

<h1>RC Attendance Full History</h1>

<div class="filter-container">
  <input type="text" id="nameFilter" placeholder="Type name to search...">
  <button id="resetFilter">Reset</button>
  <button style="background-color: red;" onclick="window.location.href='index.php'">View Datewise Record</button>
</div>

<div class="table-container">
  <?php
  // Connect to database
  $servername = "localhost:3306";
  $username = "rc-attendance-system";
  $password = "rc-attendance-system";
  $dbname = "rc-attendance-system";

  try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch ALL records without filtering by date
    $query = "SELECT ID, name, timestamp, action FROM data ORDER BY timestamp DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $conn = null;

    if (count($records) > 0) {
      echo '<table id="attendanceTable">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Timestamp</th>
                  <th>Action</th>
                  <th>View Photo</th>
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

        echo '<tr class="' . $rowClass . '">
                <td>'.htmlspecialchars($row['ID']).'</td>
                <td>'.htmlspecialchars($row['name']).'</td>
                <td>'.htmlspecialchars($row['timestamp']). $permissionMessage.'</td>
                <td class="'.$actionClass.'">'.htmlspecialchars($rowClass).'</td>
                <td><a href="#" class="view-photo-link" data-id="'.htmlspecialchars($row['ID']).'">View Photo</a></td>
              </tr>';
      }
      echo '</tbody></table>';
    } else {
      echo '<p>No attendance records found.</p>';
    }

  } catch(PDOException $e) {
    echo '<div style="color: red; padding: 10px; border: 1px solid red; margin: 10px 0;">
            Database Error: '.htmlspecialchars($e->getMessage()).'
          </div>';
  }
  ?>
</div>

<!-- Photo Toast -->
<div id="photoToast" class="photo-toast">
  <div class="loading-spinner" id="loadingSpinner"></div>
  <img id="photoImage" src="" alt="Attendance Photo">
  <button id="closeToast">Close</button>
</div>

<script>
  // Handle view photo links
  const photoLinks = document.querySelectorAll('.view-photo-link');
  const photoToast = document.getElementById('photoToast');
  const photoImage = document.getElementById('photoImage');
  const loadingSpinner = document.getElementById('loadingSpinner');
  const closeToast = document.getElementById('closeToast');

  photoLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      const recordId = this.getAttribute('data-id');

      loadingSpinner.style.display = 'block';
      photoImage.style.display = 'none';
      photoToast.style.display = 'block';

      fetch(`get_photo.php?id=${recordId}`)
        .then(response => response.json())
        .then(data => {
          if (data.photo) {
            photoImage.src = data.photo;
            photoImage.style.display = 'block';
          } else {
            alert('No photo available for this record.');
          }
        })
        .catch(error => {
          console.error('Error fetching photo:', error);
        })
        .finally(() => {
          loadingSpinner.style.display = 'none';
        });
    });
  });

  closeToast.addEventListener('click', function() {
    photoToast.style.display = 'none';
  });

  // Name filter logic
  const nameFilter = document.getElementById('nameFilter');
  const resetButton = document.getElementById('resetFilter');
  const tableRows = document.querySelectorAll('#attendanceTable tbody tr');

  nameFilter.addEventListener('input', function() {
    const filterValue = this.value.toLowerCase();
    tableRows.forEach(row => {
      const nameCell = row.querySelector('td:nth-child(2)');
      if (nameCell) {
        const nameText = nameCell.textContent.toLowerCase();
        row.style.display = nameText.includes(filterValue) ? '' : 'none';
      }
    });
  });

  resetButton.addEventListener('click', function() {
    nameFilter.value = '';
    tableRows.forEach(row => {
      row.style.display = '';
    });
  });
</script>

</body>
</html>
