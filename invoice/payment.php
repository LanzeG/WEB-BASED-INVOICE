<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@10/dist/sweetalert2.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

<!-- SweetAlert JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .icon-pink {
            color: #FF4081;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .btn-pink {
            background-color: #FFCDD2;
            color: #FF4081;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .btn-pink:hover {
            background-color: #f7598d;
            color: #FFF; 
        }

        .btn-pink1 {
            background-color: #ffffff;
            border: 1px solid #FF4081;
            color: #FF4081;
            transition: background-color 0.3s ease, color 0.3s ease;
            padding: 10px;
        }

        .btn-pink1:hover {
            background-color: #f7598d;
            color: #FFF; 
        }

        .btn-pink:hover .icon-pink {
            color: #FFF; /* Change the icon color to white */
        }
        .poppins-regular {
        font-family: "Poppins", sans-serif;
        font-weight: 400;
        font-style: normal;
        }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto bg-white shadow-md rounded-lg p-6">
        <h1 class="text-2xl font-bold mb-6 text-center">Invoice Management</h1>
        <div class="flex items-center justify-start gap-4 mb-2">
            <button onclick="window.location.href = 'index.html'" class="btn-pink1 px-4 py-2 rounded-md shadow flex items-center justify-center">
                <i class="fas fa-calculator icon-pink mr-2"></i> Calculator
                
            </button>
            <button onclick="window.location.href = 'dashboard.php'" class="btn-pink1 px-4 py-2 rounded-md shadow flex items-center justify-center">
                <i class="fas fa-chart-line icon-pink mr-2"></i> Dashboard
            </button>
        </div>
        <?php
        $servername = "localhost:3307";
        $username = "root";
        $password = "";
        $dbname = "invoice_db";

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['invoice_id'])) {
            $invoice_id = $_POST['invoice_id'];
            $status = $_POST['status'];
            $sql = "UPDATE invoices SET status='$status' WHERE id=$invoice_id";
            if ($conn->query($sql) === TRUE) {
                echo "<script>
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Updated',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                      </script>";
            } else {
                echo "<script>
                        Swal.fire({
                            icon: 'error',
                            title: 'Error updating record: " . $conn->error . "',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                      </script>";
            }
        }
        

        $date_sql = "SELECT DISTINCT invoice_date FROM invoices ORDER BY invoice_date ASC";
        $date_result = $conn->query($date_sql);

        $selected_date = isset($_GET['invoice_date']) ? $_GET['invoice_date'] : '';

        $sql = "SELECT id, customer_name, total, invoice_date, status FROM invoices";
        if (!empty($selected_date)) {
            $sql .= " WHERE invoice_date = '$selected_date'";
        }
        $sql .= " ORDER BY invoice_date ASC";
        $result = $conn->query($sql);

        $total_paid = 0;
        $total_unpaid = 0;
        

        if (!empty($selected_date)) {
            $total_sql = "SELECT status, SUM(total) as total_amount FROM invoices WHERE invoice_date = '$selected_date' GROUP BY status";
            $total_result = $conn->query($total_sql);

            if ($total_result->num_rows > 0) {
                while ($total_row = $total_result->fetch_assoc()) {
                    if ($total_row['status'] == 'paid') {
                        $total_paid = $total_row['total_amount'];
                    } elseif ($total_row['status'] == 'unpaid') {
                        $total_unpaid = $total_row['total_amount'];
                    }
                }
            }
        }

        echo "<form method='GET' action='payment.php' class='mb-4'>
                <label for='invoice_date' class='block text-sm font-medium text-gray-700'>Select Invoice Date:</label>
                <select name='invoice_date' id='invoice_date' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'>
                    <option value=''>All Dates</option>";
        if ($date_result->num_rows > 0) {
            while ($date_row = $date_result->fetch_assoc()) {
                $date = $date_row['invoice_date'];
                echo "<option value='$date'" . ($selected_date == $date ? ' selected' : '') . ">$date</option>";
            }
        }
        echo "  </select>
                <input type='submit' value='FILTER' class='btn-pink px-4 py-2 mt-2 rounded-md shadow'>
              </form>";


            if (!empty($selected_date)) {
                echo "<p class='text-l font-semibold mb-2 text-red-200'>Total Paid for $selected_date</p>";
                echo "<div class='flex mb-4'>";
                echo "<div class='card bg-green-100 shadow-md rounded-lg p-4 mr-4'>";
                
                echo "<p class='text-green-500'>Php $total_paid</p>";
                echo "</div>";
                
                echo "<div class='card bg-pink-100 shadow-md rounded-lg p-4'>";
                echo "<p class='text-red-500'>Php $total_unpaid</p>";
                echo "</div>";
                
                echo "</div>"; // Close flex container
            }
        if ($result->num_rows > 0) {
            echo "<div class='overflow-x-auto'>
                    <table class='min-w-full bg-white shadow-md rounded-lg overflow-hidden'>
                        <thead class='bg-gray-200'>
                            <tr>
                                <th class='py-3 px-4 text-left text-sm font-semibold text-gray-600'>Invoice ID</th>
                                <th class='py-3 px-4 text-left text-sm font-semibold text-gray-600'>Customer Name</th>
                                <th class='py-3 px-4 text-left text-sm font-semibold text-gray-600'>Total</th>
                                <th class='py-3 px-4 text-left text-sm font-semibold text-gray-600'>Invoice Date</th>
                                <th class='py-3 px-4 text-left text-sm font-semibold text-gray-600'>Status</th>
                                <th class='py-3 px-4 text-left text-sm font-semibold text-gray-600'>Action</th>
                            </tr>
                        </thead>
                        <tbody>";

            while($row = $result->fetch_assoc()) {
                echo "<tr class='bg-white border-b'>
                <td class='py-3 px-4 text-gray-700'>{$row['id']}</td>
                <td class='py-3 px-4 text-gray-700'>{$row['customer_name']}</td>
                <td class='py-3 px-4 text-gray-700'>{$row['total']}</td>
                <td class='py-3 px-4 text-gray-700'>{$row['invoice_date']}</td>
                <td class='py-3 px-4 text-gray-700'>{$row['status']}</td>
                <td class='py-3 px-4'> 
                <form id='paymentForm{$row['id']}' method='POST' action='payment.php" . (!empty($selected_date) ? "?invoice_date=" . urlencode($selected_date) : "") . "'>
                <input type='hidden' name='invoice_id' value='{$row['id']}'>
                <select name='status' id='status' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm' onchange='submitForm(\"paymentForm{$row['id']}\")'>
                    <option value='paid' ". ($row['status'] == 'paid' ? 'selected' : '') . ">PAID</option>
                    <option value='unpaid' ". ($row['status'] == 'unpaid' ? 'selected' : '') . ">UNPAID</option>
                    </select>
                </form>
            </td>
        </tr>";

            }

            echo "    </tbody>
                    </table>
                  </div>";
        } else {
            echo "<div class='text-center text-gray-500'>No invoices found</div>";
        }

        $conn->close();
        ?>
          <script>
            function submitForm(formId) {
                document.getElementById(formId).submit();
            }
        </script>
    </div>
</body>
</html>
