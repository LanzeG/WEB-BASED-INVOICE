function addItem() {
    let name = document.getElementById('name').value;
    let number = document.getElementById('number').value;
    sendRequest('add', name, number);
}

function addPredefinedPrice(price) {
    let name = document.getElementById('name').value;
    if (name === '') {
        alert('Please enter a customer name first.');
        return;
    }
    sendRequest('add', name, price);
}

function undoItem() {
    let name = document.getElementById('name').value;
    sendRequest('undo', name);
}

function clearName() {
    document.getElementById("name").value = "";
}

function calculateTotal() {
    sendRequest('calculate');
}

function createNewInvoice() {
    // Clear the session data for the current invoice
    sendRequest('clearInvoiceData');
}
function printInvoice() {
    sendRequest('print');
}

function saveInvoice() {
    sendRequest('save');
}
function sendRequest(action, name = '', number = '') {
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "invoicefunction.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
            if (action === 'add') {
                let newData = JSON.parse(xhr.responseText);
                let newEntry = Object.keys(newData)[0];
                let newValues = newData[newEntry].join(', ');
                document.getElementById('newEntriesArea').innerHTML = '"' + newEntry + '": [' + newValues + ']';
            } else if (action === 'generateNewInvoice' || action === 'clearInvoiceData') {
                document.getElementById('outputArea').innerHTML = xhr.responseText;
            } else {
                document.getElementById('outputArea').innerHTML = xhr.responseText;
            }
        }
    };
    xhr.send("action=" + action + "&name=" + name + "&number=" + number);
}




// Load initial data
window.onload = function() {
    fetchAndDisplayAllInvoiceData();
}

function fetchAndDisplayAllInvoiceData() {
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "invoicefunction.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
            document.getElementById('outputArea').innerHTML = xhr.responseText;
        }
    };
    xhr.send("action=load");
}



function printInvoice() {
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "invoicefunction.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.responseType = 'blob';
    xhr.onload = function () {
        if (xhr.status === 200) {
            let blob = xhr.response;
            let link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = 'Invoice_' + new Date().toISOString().split('T')[0] + '.txt';
            link.click();
        }
    };
    xhr.send("action=print");
}
