<?php
try {
    $pdo = new PDO('sqlite::memory:');
    echo "SQLite version: " . $pdo->query('select sqlite_version()')->fetch()[0];
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
