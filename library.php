<?php
// Helper function to draw an HTML table from an associative array
function draw_table($data)
{
    if (empty($data))
    {
        echo "<p>No data to display.</p>";
        return;
    }
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    
    // Table headers
    echo "<tr>";
    foreach (array_keys($data[0]) as $header)
    {
        echo "<th>" . htmlspecialchars($header) . "</th>";
    }
    echo "</tr>";
    
    // Table rows
    foreach ($data as $row)
    {
        echo "<tr>";
        foreach ($row as $cell)
        {
            echo "<td>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
}

// Function to calculate quote total from line items
function calculate_quote_total($pdo, $quote_id)
{
    $prepared = $pdo->prepare("SELECT SUM(PRICE) as TOTAL FROM LINE_ITEMS WHERE QUOTE_ID = ?;");
    $prepared->execute(array($quote_id));
    $result = $prepared->fetch(PDO::FETCH_ASSOC);
    return $result['TOTAL'] ? $result['TOTAL'] : 0;
}

// Function to apply discount
function apply_discount($price, $discount, $discount_type)
{
    if ($discount_type == 'percentage')
    {
        return $price - ($price * ($discount / 100));
    }
    else // amount
    {
        return $price - $discount;
    }
}

// Function to send email
function send_quote_email($to_email, $subject, $body)
{
    $headers = "From: quotes@plantrepair.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to_email, $subject, $body, $headers);
}

// Function to format quote email body (without secret notes)
function format_quote_email($quote_data, $line_items, $include_po_info = false, $processing_date = null)
{
    $body = "<html><body>";
    $body .= "<h2>Quote #" . $quote_data['QUOTE_ID'] . "</h2>";
    $body .= "<p><strong>Date:</strong> " . $quote_data['DATE'] . "</p>";
    $body .= "<p><strong>Customer ID:</strong> " . $quote_data['CUSTOMER_ID'] . "</p>";
    
    $body .= "<h3>Line Items:</h3>";
    $body .= "<table border='1' cellpadding='5'>";
    $body .= "<tr><th>Item #</th><th>Description</th><th>Price</th></tr>";
    
    foreach ($line_items as $item)
    {
        $body .= "<tr>";
        $body .= "<td>" . $item['ITEM_NUMBER'] . "</td>";
        $body .= "<td>" . $item['DESCRIPTION'] . "</td>";
        $body .= "<td>$" . number_format($item['PRICE'], 2) . "</td>";
        $body .= "</tr>";
    }
    
    $body .= "</table>";
    $body .= "<p><strong>Total Price:</strong> $" . number_format($quote_data['PRICE'], 2) . "</p>";
    
    if ($include_po_info && $processing_date)
    {
        $body .= "<p><strong>Processing Date:</strong> " . $processing_date . "</p>";
        $body .= "<p>Your order has been confirmed and will be processed on the date shown above.</p>";
    }
    
    $body .= "<p>Thank you for your business!</p>";
    $body .= "</body></html>";
    
    return $body;
}

// Function to call external processing system
function call_external_processing_system($quote_id, $customer_id, $total_price)
{
    // Simulate external API call
    // In real implementation, this would make an actual HTTP request
    // For now, return mock data
    
    $processing_date = date('Y-m-d', strtotime('+14 days'));
    $commission_rate = 0.05; // 5% commission rate
    
    return array(
        'processing_date' => $processing_date,
        'commission_rate' => $commission_rate
    );
}
?>
