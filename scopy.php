<!DOCTYPE html>

<?php
// Path to the asset-manifest.json file
$manifestPath = 'path/to/build/asset-manifest.json';

// Read the manifest file
$manifest = json_decode(file_get_contents($manifestPath), true);

// Get the paths for the main CSS and JS files
$mainCss = $manifest['files']['main.css'];
$mainJs = $manifest['files']['main.js'];
?>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pdf Scopy</title>
  <!-- Include the main CSS file -->
  <link rel="stylesheet" href="<?php echo $mainCss; ?>" />
</head>

<body>
  <div id="root"></div>
  <!-- Include the main JS file -->
  <script src="<?php echo $mainJs; ?>"></script>
</body>

</html>