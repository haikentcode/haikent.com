<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create or open the SQLite database file
    $db = new SQLite3('pdf_text.db');

    // Check if the database connection was successful
    if (!$db) {
        echo "Failed to connect to the database.";
        exit();
    }

    // Get the Hindi text line from the query parameter
    $hindiLine = isset($_GET['query']) ? $_GET['query'] : '';

    // Split the Hindi text line into individual words
    $hindiWords = preg_split('/\s+/', $hindiLine, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($myArray as $hindiWords) {
        echo $value . "<br>";
    }

    // Create an array to store the results with scores
    $results = array();

    // Loop through each word in the Hindi text line
    foreach ($hindiWords as $hindiWord) {
        // Construct the regex pattern with the Hindi word
        $pattern = '/\b' . preg_quote($hindiWord, '/') . '\b/i'; // Case-insensitive search

        // Query to search for rows containing the specified Hindi word in the raw_text_data column
        $query = "SELECT * FROM pdf_text_data WHERE raw_text_data LIKE :pattern";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':pattern', $pattern, SQLITE3_TEXT);

        // Execute the query
        $result = $stmt->execute();

        // Check if the query was successful
        if ($result) {
            // Loop through the result set and calculate a score for each result
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                // Calculate a score based on the number of matches in the raw_text_data
                $score = preg_match_all($pattern, $row['raw_text_data'], $matches);

                // Store the result with the score
                $results[] = array(
                    'pdf_name' => $row['pdf_name'],
                    'page_number' => $row['page_number'],
                    'raw_text_data' => $row['raw_text_data'],
                    'score' => $score,
                );
            }
        }
    }

    // Sort the results by score in descending order (higher score first)
    usort($results, function ($a, $b) {
        return $b['score'] - $a['score'];
    });

    // Display the results with scores
    echo "<html><head><title>Search Results</title></head><body>";
    echo "<h1>Search Results</h1>";

    if (!empty($results)) {
        echo "<table border='1'>";
        echo "<tr><th>PDF Name</th><th>Page Number</th><th>Raw Text Data</th><th>Score</th></tr>";

        foreach ($results as $result) {
            echo "<tr>";
            echo "<td>" . $result['pdf_name'] . "</td>";
            echo "<td>" . $result['page_number'] . "</td>";
            echo "<td>" . $result['raw_text_data'] . "</td>";
            echo "<td>" . $result['score'] . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "No matching rows found.";
    }

    echo "</body></html>";

    // Close the database connection
    $db->close();
} catch (Exception $e) {
    echo "An error occurred: " . $e->getMessage();
}
?>
