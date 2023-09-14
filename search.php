<?php


 try {
      
      $pdo = new SQLite3('./pdf_text.db');

  } catch (Exception $e) {
      echo "SQLite error: " . $e->getMessage();
}


// if (isset($_GET['query'])) {
//   $searchQuery = $_GET['query'];

//   // Perform a database search based on the query
//   // Assume you have already connected to the database
//   // and have prepared a statement to search for the query

//   // Example SQL query:
//   $sql = "SELECT pdf_name, page_number, raw_text_data FROM pdf_text_data WHERE raw_text_data LIKE :query";
//   $stmt = $pdo->prepare($sql);
//   $stmt->bindParam(':query', $searchQuery, PDO::PARAM_STR);
//   $stmt->execute();

//   // Display search results as HTML
//   $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
//   if (!empty($results)) {
//       foreach ($results as $result) {
//           echo "<div>";
//           echo "<h3>PDF Name: {$result['pdf_name']}</h3>";
//           echo "<p>Page Number: {$result['page_number']}</p>";
//           echo "<p>Raw Text: {$result['raw_text_data']}</p>";
//           echo "</div>";
//       }
//   } else {
//       echo "<p>No matching results found.</p>";
//   }
// }



?>
