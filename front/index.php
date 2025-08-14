<?php
/**
 * Plugin LAPS-GLPI
 * Security file to prevent directory listing
 */

// Redirect to GLPI root
header('Location: ../../../index.php');
exit();
?>