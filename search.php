<?php

 try {
      
      $db = new SQLite3('pdf_text.db');

      if (isset($_GET['query'])) {

            $searchQuery = $_GET['query'];
            echo "Hello search ->".$searchQuery."  ".$db ;

      }

  } catch (Exception $e) {
      echo "SQLite error: " . $e->getMessage();
}

?>
