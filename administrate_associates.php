<html><head><title>Sales Associates</title></head><body>
<?php
    include("secret.php");

    try
    {
		$pdo = new PDO($dsn, $username, $password);
		
		// If the user wanted to add a new sales associate, then add it.
		if (! empty($_POST["add_associate"]))
		{
			$prepared = $pdo->prepare("INSERT INTO SALES_ASSOCIATES(NAME, PASSWORD)
									       VALUES(?, ?);");
			$prepared->execute(array($_POST["name"], $_POST["password"]));
		}
		// If the user wanted to delete an associate, do so.
		else if (! empty($_POST["delete_associate"]))
		{
			$prepared = $pdo->prepare("DELETE FROM SALES_ASSOCIATES
									   WHERE ASSOCIATE_ID = ?;");
			$prepared->execute(array($_POST["associate_id"]));
		}
		
		// Get all the sales associates' information.
		$rs = $pdo->query("SELECT * FROM SALES_ASSOCIATES;");
		$associates = $rs->fetchAll(PDO::FETCH_ASSOC);
		
		// A form to edit or delete a sales associate.
		echo "<form method=\"POST\">";
		
		// Show each associate's information and edit and delete buttons.
		foreach($associates as $row)
		{
			// Show each associate's information.
			echo $row["ASSOCIATE_ID"].". ".$row["NAME"].": Password: ".$row["PASSWORD"].", Address: ".$row["ADDRESS"].", Sales commission: $".$row["SALES_COMMISSION"];
			
			// Send the associate's identification number.
			echo "<input type=\"hidden\" name=\"associate_id\" value=\"".$row["ASSOCIATE_ID"]."\">";
			
			// Edit and delete buttons.
			echo " <input type=\"submit\" name=\"edit_associate\" value=\"Edit\" formaction=\"edit_associate.php\">";
			echo " <input type=\"submit\" name=\"delete_associate\" value=\"Delete\" formaction=\"administrate_associates.php\"><br/>";
		}
		
		// End of form.
		echo "</form>";
		
		// A form to add a new sales associate.
		echo "<br/>Add a new sales associate:";
		echo "<br/><form action=\"administrate_associates.php\" method=\"POST\">";
		echo "Name: <input type=\"text\" name=\"name\" required> ";
		echo "Password: <input type=\"password\" name=\"password\" required>";
		echo "<br/><input type=\"submit\" name=\"add_associate\" value=\"Add\">";
		echo "</form>";
    }
    catch(PDOexception $e)
    {
		echo "Connection to database failed: ".$e->getMessage();
    }
?>
</body></html>
