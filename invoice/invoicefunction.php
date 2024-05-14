<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $name = $_POST['name'] ?? '';
    $number = isset($_POST['number']) ? (int)$_POST['number'] : 0;

    switch ($action) {
        case 'add':
            if (!isset($_SESSION['invoiceData'][$name])) {
                $_SESSION['invoiceData'][$name] = [];
            }
            $_SESSION['invoiceData'][$name][] = $number;
            $_SESSION['totalSum'] += $number;
            echo json_encode([$name => $_SESSION['invoiceData'][$name]]);
            break;

        case 'undo':
            if (isset($_SESSION['invoiceData'][$name]) && count($_SESSION['invoiceData'][$name]) > 0) {
                $lastValue = array_pop($_SESSION['invoiceData'][$name]);
                $_SESSION['totalSum'] -= $lastValue;
            }
            echo json_encode($_SESSION['invoiceData']);
            break;

        case 'clearName':
            unset($_SESSION['invoiceData'][$name]);
            echo json_encode($_SESSION['invoiceData']);
            break;

        case 'calculate':
            $output = generateInvoiceText();
            echo nl2br($output);
            break;

        case 'invoice':
            $invoiceText = generateInvoiceText();
            echo nl2br($invoiceText);
            break;

        case 'save':
            saveInvoiceData($conn);
            break;

        case 'print':
            printInvoice();
            break;

        case 'load':
            echo json_encode($_SESSION['invoiceData']);
            break;

        case 'generateNewInvoice':
            generateNewInvoice();
            break;

        case 'clearInvoiceData':
            clearInvoiceData();
            break;

        case 'saveEmptyInvoice':
            saveEmptyInvoice($conn);
            break;

        default:
            echo "Invalid action!";
            break;
    }
}

function generateInvoiceText() {
    $output = "Love What to Wear PH\n";
    $output .= "Date: " . date("Y-m-d H:i:s") . "\n\n";
    foreach ($_SESSION['invoiceData'] as $name => $numbers) {
        $sum = array_sum($numbers);
        $quantity = count($numbers);
        $output .= "$name (Quantity: $quantity): " . implode(", ", $numbers) . " = $sum\n";
    }
    $output .= "\nTotal sum: " . $_SESSION['totalSum'];
    return $output;
}

function saveInvoiceData($conn) {
    foreach ($_SESSION['invoiceData'] as $name => $numbers) {
        $sum = array_sum($numbers);
        $date = date("Y-m-d");
        $sql_invoice = "INSERT INTO invoices (customer_name, total, invoice_date) VALUES ('$name', $sum, '$date')";
        if ($conn->query($sql_invoice) === TRUE) {
            $invoice_id = $conn->insert_id;
            foreach ($numbers as $price) {
                // Assuming quantity is always 1 for each item
                $sql_item = "INSERT INTO invoice_items (invoice_id, price, quantity) VALUES ($invoice_id, $price, 1)";
                $conn->query($sql_item);
            }
        } else {
            echo "Error: " . $sql_invoice . "<br>" . $conn->error;
        }
    }
    echo "Invoice data saved successfully.";
}



function printInvoice() {
    $invoiceText = generateInvoiceText();
    $date = date("Y-m-d");
    $fileName = "Invoice_$date.txt";
    
    // Set headers to trigger file download
    header('Content-Description: File Transfer');
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . strlen($invoiceText));
    header('Pragma: public');

    // Output the file content
    echo $invoiceText;
    exit();
}

function generateNewInvoice() {
    $output = "";
    foreach ($_SESSION['invoiceData'] as $name => $numbers) {
        $sum = array_sum($numbers);
        $quantity = count($numbers);
        $output .= "$name (Quantity: $quantity): " . implode(", ", $numbers) . " = $sum\n";
    }
    $output .= "\nTotal sum: " . $_SESSION['totalSum'];
    echo $output;
}

function clearInvoiceData() {
    unset($_SESSION['invoiceData']);
    $_SESSION['invoiceData'] = [];
    $_SESSION['totalSum'] = 0;
    echo "New invoice created successfully.";
}

function saveEmptyInvoice($conn) {
    $date = date("Y-m-d H:i:s");
    $sql = "INSERT INTO invoices (customer_name, quantity, total, date_created) VALUES ('', 0, 0, '$date')";
    if ($conn->query($sql) === TRUE) {
        echo "Empty invoice data saved to the database.";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
?>


