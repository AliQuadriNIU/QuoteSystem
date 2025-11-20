<?php
// Legacy Customer Database Connection Configuration
// IMPORTANT: This file should never be committed to version control!

// Customer database connection (separate from quote system database)
$customer_dsn = "mysql:host=blitz.cs.niu.edu;port=3306;dbname=csci467;charset=utf8mb4";
$customer_username = "student";
$customer_password = "student";

// SSL options for connecting from outside NIU network
$customer_options = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    // Uncomment the following lines if connecting from outside NIU network
    // PDO::MYSQL_ATTR_SSL_CA => '/path/to/ca-cert.pem',
    // PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
);

// Function to get customer database connection
function get_customer_db_connection()
{
    global $customer_dsn, $customer_username, $customer_password, $customer_options;
    try {
        return new PDO($customer_dsn, $customer_username, $customer_password, $customer_options);
    } catch (PDOException $e) {
        error_log("Customer DB connection failed: " . $e->getMessage());
        return null;
    }
}

// Function to search customers by name
function search_customers_by_name($search_term)
{
    $pdo = get_customer_db_connection();
    if (!$pdo) return array();
    
    $prepared = $pdo->prepare("SELECT * FROM customers WHERE name LIKE ? ORDER BY name;");
    $prepared->execute(array("%" . $search_term . "%"));
    return $prepared->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get customer by ID
function get_customer_by_id($customer_id)
{
    $pdo = get_customer_db_connection();
    if (!$pdo) return null;
    
    $prepared = $pdo->prepare("SELECT * FROM customers WHERE id = ?;");
    $prepared->execute(array($customer_id));
    return $prepared->fetch(PDO::FETCH_ASSOC);
}

// Function to get all customers
function get_all_customers()
{
    $pdo = get_customer_db_connection();
    if (!$pdo) return array();
    
    $rs = $pdo->query("SELECT * FROM customers ORDER BY name;");
    return $rs->fetchAll(PDO::FETCH_ASSOC);
}

// Function to validate customer ID exists
function validate_customer_id($customer_id)
{
    $customer = get_customer_by_id($customer_id);
    return ($customer !== null && $customer !== false);
}
?>
