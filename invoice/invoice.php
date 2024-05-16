<?php
header('Content-Type: application/json');

// Connect to the database
$conn = new mysqli("localhost:3307", "root", "", "invoice_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$date = isset($_GET['date']) ? $_GET['date'] : '';

// Fetch customer data based on the selected date
$invoiceData = [];
if ($date) {
    $sql = "SELECT customer_name, SUM(total) AS total_payments FROM invoices WHERE invoice_date = ? GROUP BY customer_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $invoiceData[] = $row;
    }
    $stmt->close();
}

// Fetch monthly revenue data
$monthlyRevenueData = [];
$sql = "SELECT DATE_FORMAT(invoice_date, '%Y-%m') AS month, SUM(total) AS revenue FROM invoices GROUP BY month ORDER BY month";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $monthlyRevenueData[$row['month']] = $row['revenue'];
}

$responseData = [
    'invoiceData' => $invoiceData,
    'monthlyRevenueData' => $monthlyRevenueData
];

echo json_encode($responseData);

$conn->close();
?>
