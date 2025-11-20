<html><head><title>Convert to Purchase Order</title></head><body>
<?php
    include("secret.php");
    include("library.php");

    try
    {
        $pdo = new PDO($dsn, $username, $password);
        
        if (empty($_POST["quote_id"]))
        {
            echo "No quote selected.";
            echo "<br/><a href=\"headquarters_home.php\">Back to HQ Home</a>";
            exit;
        }
        
        $quote_id = $_POST["quote_id"];
        
        // Handle conversion
        if (!empty($_POST["convert_to_po"]))
        {
            // Get current quote data
            $prepared = $pdo->prepare("SELECT * FROM QUOTES WHERE QUOTE_ID = ?;");
            $prepared->execute(array($quote_id));
            $quote = $prepared->fetch(PDO::FETCH_ASSOC);
            
            $current_price = $quote['PRICE'];
            
            // Apply final discount if provided
            if (!empty($_POST["final_discount"]) && $_POST["final_discount"] > 0)
            {
                $final_price = apply_discount($current_price, $_POST["final_discount"], $_POST["discount_type"]);
            }
            else
            {
                $final_price = $current_price;
            }
            
            // Call external processing system
            $external_response = call_external_processing_system($quote_id, $quote['ASSOCIATE_ID'], $quote['CUSTOMER_ID'], $final_price);
            
            // Check if API call was successful
            if (!$external_response['success']) {
                echo "<p style='color: red;'><strong>Warning:</strong> External processing system error: " . htmlspecialchars($external_response['error']) . "</p>";
                echo "<p>Using default values for processing date and commission rate.</p>";
            }
            
            $processing_date = $external_response['processing_date'];
            $commission_rate = $external_response['commission_rate'];
            
            // Calculate commission
            $commission = $final_price * $commission_rate;
            
            // Update quote status and price
            $prepared = $pdo->prepare("UPDATE QUOTES SET PRICE = ?, STATUS = 'Ordered' WHERE QUOTE_ID = ?;");
            $prepared->execute(array($final_price, $quote_id));
            
            // Update sales associate's accumulated commission
            $prepared = $pdo->prepare("UPDATE SALES_ASSOCIATES 
                                       SET SALES_COMMISSION = SALES_COMMISSION + ? 
                                       WHERE ASSOCIATE_ID = ?;");
            $prepared->execute(array($commission, $quote['ASSOCIATE_ID']));
            
            // Get line items for email
            $prepared = $pdo->prepare("SELECT * FROM LINE_ITEMS WHERE QUOTE_ID = ?;");
            $prepared->execute(array($quote_id));
            $line_items = $prepared->fetchAll(PDO::FETCH_ASSOC);
            
            // Update quote data with new price for email
            $quote['PRICE'] = $final_price;
            
            // Send email to customer
            $email_body = format_quote_email($quote, $line_items, true, $processing_date);
            $email_sent = send_quote_email($quote['CUSTOMER_EMAIL'], "Purchase Order Confirmed - Quote #$quote_id", $email_body);
            
            echo "<h2>Purchase Order Created Successfully!</h2>";
            echo "<p style='color: green;'><strong>Quote #$quote_id has been converted to a purchase order.</strong></p>";
            echo "<p><strong>Final Price:</strong> $" . number_format($final_price, 2) . "</p>";
            echo "<p><strong>Processing Date:</strong> " . $processing_date . "</p>";
            echo "<p><strong>Sales Commission Rate:</strong> " . ($commission_rate * 100) . "%</p>";
            echo "<p><strong>Sales Commission:</strong> $" . number_format($commission, 2) . "</p>";
            
            if ($email_sent)
            {
                echo "<p style='color: green;'>Purchase order email sent to customer successfully.</p>";
            }
            else
            {
                echo "<p style='color: orange;'>Warning: Email sending failed. Please contact customer manually.</p>";
            }
            
            echo "<br/><a href=\"headquarters_home.php\">Back to HQ Home</a>";
            exit;
        }
        
        // Display conversion form
        $prepared = $pdo->prepare("SELECT q.*, sa.NAME as ASSOCIATE_NAME 
                                   FROM QUOTES q 
                                   JOIN SALES_ASSOCIATES sa ON q.ASSOCIATE_ID = sa.ASSOCIATE_ID 
                                   WHERE q.QUOTE_ID = ?;");
        $prepared->execute(array($quote_id));
        $quote = $prepared->fetch(PDO::FETCH_ASSOC);
        
        if (!$quote)
        {
            echo "<p style='color: red;'>Quote not found.</p>";
            echo "<br/><a href=\"headquarters_home.php\">Back to HQ Home</a>";
            exit;
        }
        
        if ($quote['STATUS'] != 'Sanctioned')
        {
            echo "<p style='color: red;'>Error: Only sanctioned quotes can be converted to purchase orders.</p>";
            echo "<p>Current status: " . $quote['STATUS'] . "</p>";
            echo "<br/><a href=\"headquarters_home.php\">Back to HQ Home</a>";
            exit;
        }
        
        echo "<h2>Convert Quote #" . $quote_id . " to Purchase Order</h2>";
        echo "<p><strong>Customer ID:</strong> " . $quote["CUSTOMER_ID"] . "</p>";
        echo "<p><strong>Customer Email:</strong> " . $quote["CUSTOMER_EMAIL"] . "</p>";
        echo "<p><strong>Sales Associate:</strong> " . $quote["ASSOCIATE_NAME"] . "</p>";
        echo "<p><strong>Date:</strong> " . $quote["DATE"] . "</p>";
        
        // Display line items
        echo "<h3>Line Items:</h3>";
        $prepared = $pdo->prepare("SELECT * FROM LINE_ITEMS WHERE QUOTE_ID = ? ORDER BY ITEM_NUMBER;");
        $prepared->execute(array($quote_id));
        $line_items = $prepared->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Item #</th><th>Description</th><th>Price</th></tr>";
        foreach ($line_items as $item)
        {
            echo "<tr>";
            echo "<td>" . $item["ITEM_NUMBER"] . "</td>";
            echo "<td>" . $item["DESCRIPTION"] . "</td>";
            echo "<td>$" . number_format($item["PRICE"], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Current Price: $" . number_format($quote["PRICE"], 2) . "</strong></p>";
        
        // Final discount form
        echo "<hr/>";
        echo "<h3>Apply Final Discount (Optional):</h3>";
        echo "<form action=\"convert_to_purchase_order.php\" method=\"POST\">";
        echo "<p>Final Discount: <input type=\"number\" name=\"final_discount\" step=\"0.01\" min=\"0\" value=\"0\"> ";
        echo "<input type=\"radio\" name=\"discount_type\" value=\"amount\" checked> Amount ";
        echo "<input type=\"radio\" name=\"discount_type\" value=\"percentage\"> Percentage</p>";
        echo "<input type=\"hidden\" name=\"quote_id\" value=\"$quote_id\">";
        echo "<br/><input type=\"submit\" name=\"convert_to_po\" value=\"Convert to Purchase Order\" onclick=\"return confirm('Are you sure you want to convert this quote to a purchase order? This action cannot be undone.');\">";
        echo "</form>";
        
        // Back button
        echo "<br/><br/><form action=\"headquarters_home.php\" method=\"POST\">";
        echo "<input type=\"submit\" value=\"Back to HQ Home\">";
        echo "</form>";
    }
    catch(PDOexception $e)
    {
        echo "Connection to database failed: ".$e->getMessage();
    }
?>
</body></html>
