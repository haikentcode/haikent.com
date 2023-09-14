<?php

 try {
      
      $db = new SQLite3('pdf_text.db');

      if (isset($_GET['query'])) {

            $searchQuery = $_GET['query'];

            $sql = "SELECT pdf_name, page_number, raw_text_data FROM pdf_text_data WHERE raw_text_data LIKE :query";
            $stmt = $db->prepare(sql);
            $stmt->bindValue(':query', '%' . $searchQuery . '%', SQLITE3_TEXT);

            // // Execute the query and fetch results
            // $results = $stmt->execute();

            echo "Hello search ->".$searchQuery."  ".SQLITE3_TEXT;

      }

  } catch (Exception $e) {
      echo "SQLite error: " . $e->getMessage();
}

?>
