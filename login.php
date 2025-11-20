<html><head><title>Group Project Login</title></head><body>
<?php
    include("secret.php");
    include("library.php");

    try
    {
		// This part is just to test that everything works.
		// It should probably be deleted at some point.
		// !! Start. !!
        $pdo = new PDO($dsn, $username, $password);
        
        $rs = $pdo->query("SELECT NAME, PASSWORD FROM SALES_ASSOCIATES;");
        $info = $rs->fetchAll(PDO::FETCH_ASSOC);
        
        draw_table($info);
        // ¡¡ End. ¡¡
		
		// Ask user to enter a name and a password.
		// The submit button sends the user to the next page.
        echo "<form action=\"sales_associate_home.php\" method=\"POST\">";
        echo "<br/>Name: <input type=\"text\" id=\"name\" name=\"name\" required>";
        echo "<br/>Password: <input type=\"password\" id=\"password\" name=\"password\" required>";
        echo "<br/><input type=\"submit\" value=\"Submit\">";
        
    }
    catch(PDOexception $e)
    {
		echo "Connection to database failed: ".$e->getMessage();
	}
?>
</body></html>
