<?php
try {
    $db = new SQLite3('pdf_text.db');

    // Check if the database connection was successful
    if (!$db) {
        echo "Failed to connect to the database.";
        exit();
    }

    $searchQuery = $_GET['query'];
    $pattern = preg_quote($searchQuery, '/');

    // Query to retrieve the first two rows
    $query = "SELECT * FROM pdf_text_data where raw_text_data like  `जिसका`";
    $result = $db->query($query);

    // Check if the query was successful
    if ($result) {

        // Loop through the result set and print each row
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            echo $row['raw_text_data']."</br>";
        }

    } else {
        echo "Query failed.";
    }

    // Close the database connection
    $db->close();
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage();
}
?>
