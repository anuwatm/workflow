<?php
echo "Drivers: " . implode(", ", PDO::getAvailableDrivers());
if (in_array("sqlite", PDO::getAvailableDrivers())) {
    echo " - SQLite OK";
} else {
    echo " - SQLite MISSING";
}
