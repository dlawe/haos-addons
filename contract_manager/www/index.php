<?php
// Einfaches HTML-Header-Fragment
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test-Seite</title>
</head>
<body>
    <h1>Hello from PHP!</h1>
    <p>Deine PHP-Version ist: " . phpversion() . "</p>
    <hr />";

// phpinfo() zeigt eine detaillierte Übersicht über die PHP-Konfiguration,
// installierte Erweiterungen etc.
phpinfo();

echo "</body></html>";
?>
