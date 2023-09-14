<?php
try {
    $db = new SQLite3('pdf_text.db');

    // Check if the database connection was successful
    if (!$db) {
        echo "Failed to connect to the database.";
        exit();
    }

    // Query to retrieve the first two rows
    $query = "SELECT * FROM my_table LIMIT 2";
    $result = $db->query($query);

    // Check if the query was successful
    if ($result) {
        echo "<html><head><title>Table Data</title></head><body>";
        echo "<h1>Table Data</h1>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Description</th></tr>";

        // Loop through the result set and print each row
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['description'] . "</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "</body></html>";
    } else {
        echo "Query failed.";
    }

    // Close the database connection
    $db->close();
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage();
}
?>
