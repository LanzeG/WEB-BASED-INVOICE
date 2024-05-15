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

        // Check if an invoice already exists for the same customer and date
        $sql_check = "SELECT id, total FROM invoices WHERE customer_name = ? AND invoice_date = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param('ss', $name, $date);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows > 0) {
            // Invoice exists
            $row = $result->fetch_assoc();
            $invoice_id = $row['id'];
            $existing_total = $row['total'];

            // Calculate new items to add
            $existing_items = [];
            $sql_items_check = "SELECT price FROM invoice_items WHERE invoice_id = ?";
            $stmt_items_check = $conn->prepare($sql_items_check);
            $stmt_items_check->bind_param('i', $invoice_id);
            $stmt_items_check->execute();
            $existing_items_result = $stmt_items_check->get_result();
            
            while ($item_row = $existing_items_result->fetch_assoc()) {
                $existing_items[] = $item_row['price'];
            }

            // Calculate the difference in numbers to find new items
            $new_items = $numbers;
            foreach ($existing_items as $existing_item) {
                if (($key = array_search($existing_item, $new_items)) !== false) {
                    unset($new_items[$key]);
                }
            }

            if (!empty($new_items)) {
                // Update the total in the invoices table
                $new_total = $existing_total + array_sum($new_items);
                $sql_update = "UPDATE invoices SET total = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param('di', $new_total, $invoice_id);
                if ($stmt_update->execute() === TRUE) {
                    // Insert new items into invoice_items
                    foreach ($new_items as $price) {
                        $sql_item = "INSERT INTO invoice_items (invoice_id, price, quantity) VALUES (?, ?, 1)";
                        $stmt_item = $conn->prepare($sql_item);
                        $stmt_item->bind_param('id', $invoice_id, $price);
                        $stmt_item->execute();
                    }
                } else {
                    echo "Error updating record: " . $conn->error;
                }
            } else {
                echo "No new items to update.";
            }
        } else {
            // Insert new invoice
            $sql_invoice = "INSERT INTO invoices (customer_name, total, invoice_date) VALUES (?, ?, ?)";
            $stmt_invoice = $conn->prepare($sql_invoice);
            $stmt_invoice->bind_param('sds', $name, $sum, $date);
            if ($stmt_invoice->execute() === TRUE) {
                $invoice_id = $stmt_invoice->insert_id;
                foreach ($numbers as $price) {
                    $sql_item = "INSERT INTO invoice_items (invoice_id, price, quantity) VALUES (?, ?, 1)";
                    $stmt_item = $conn->prepare($sql_item);
                    $stmt_item->bind_param('id', $invoice_id, $price);
                    $stmt_item->execute();
                }
            } else {
                echo "Error: " . $sql_invoice . "<br>" . $conn->error;
            }
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


