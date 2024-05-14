<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6 text-center">Invoice Dashboard</h1>
        <div class="mb-4">
            <label for="invoiceDatesDropdown" class="block text-sm font-medium text-gray-700">Select Date:</label>
            <div class="flex">
                <select id="invoiceDatesDropdown" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Select Date</option>
                    <?php
                    // Connect to the database
                    $conn = new mysqli("localhost:3307", "root", "", "invoice_db");

                    // Fetch distinct invoice dates from the database
                    $sql = "SELECT DISTINCT invoice_date FROM invoices ORDER BY invoice_date DESC";
                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['invoice_date'] . "'>" . $row['invoice_date'] . "</option>";
                    }
                    ?>
                </select>
                <button id="applyButton" class="ml-2 px-4 py-2 bg-blue-500 text-white rounded-md shadow-sm hover:bg-blue-700">Apply</button>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold mb-4">Monthly Revenue</h2>
                <canvas id="monthlyRevenueChart"></canvas>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold mb-4">Invoice Summary</h2>
                <div class="overflow-x-auto">
                    <table id="invoiceTable" class="table-auto w-full">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-2">Name</th>
                                <th class="px-4 py-2">Total Payments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Invoice data will be dynamically populated here -->
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-100">
                                <td class="px-4 py-2 font-semibold">Total Revenue</td>
                                <td id="overallRevenue" class="px-4 py-2 font-semibold"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        let monthlyRevenueChart = null;

        function fetchDataFromServer(date) {
            fetch('invoice.php?date=' + date)
                .then(response => response.json())
                .then(data => {
                    updateMonthlyRevenueChart(data.monthlyRevenueData);
                    updateInvoiceTable(data.invoiceData);
                })
                .catch(error => console.error('Error:', error));
        }

        function updateMonthlyRevenueChart(data) {
            const labels = Object.keys(data);
            const values = Object.values(data);
            const ctx = document.getElementById('monthlyRevenueChart').getContext('2d');

            if (monthlyRevenueChart) {
                monthlyRevenueChart.destroy();
            }

            monthlyRevenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Monthly Revenue',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        data: values,
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function updateInvoiceTable(data) {
            const tableBody = document.querySelector('#invoiceTable tbody');
            tableBody.innerHTML = '';
            let totalRevenue = 0;
            data.forEach(invoice => {
                const row = `
                    <tr>
                        <td class='px-4 py-2'>${invoice.customer_name}</td>
                        <td class='px-4 py-2'>${invoice.total_payments}</td>
                    </tr>
                `;
                tableBody.innerHTML += row;
                totalRevenue += parseFloat(invoice.total_payments);
            });
            document.getElementById('overallRevenue').textContent = totalRevenue.toFixed(2);
        }

        document.getElementById('applyButton').addEventListener('click', () => {
            const selectedDate = document.getElementById('invoiceDatesDropdown').value;
            fetchDataFromServer(selectedDate);
        });

        // Initial fetch for the default date
        fetchDataFromServer('');
    </script>
</body>
</html>
