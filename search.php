<?php
try {
    $db = new SQLite3('pdf_text.db');

    // Check if the database connection was successful
    if (!$db) {
        echo "Failed to connect to the database.";
        exit();
    }

    $searchQuery = $_GET['query'];
    $line = preg_quote($searchQuery, '/');

    // Construct the regex pattern to match the exact line in the description column
    $pattern = '/^' . $line . '$/i'; // Use the "i" flag for case-insensitivity



    $query = "SELECT * FROM pdf_text_data WHERE raw_text_data REGEXP :pattern";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':pattern', $pattern, SQLITE3_TEXT);

    // Query to retrieve the first two rows
    // $query = "SELECT * FROM pdf_text_data LIMIT 5";
    // $result = $db->query($query);

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
