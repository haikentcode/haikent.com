<?php


try {
  $db = new SQLite3('pdf_text.db');
  if ($db) {
      echo "Connected to the database.";
  } else {
      echo "Failed to connect to the database.";
  }
} catch (Exception $e) {
  echo "An error occurred: " . $e->getMessage();
}

?>
