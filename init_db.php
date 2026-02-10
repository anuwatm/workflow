<?php
$dbPath = __DIR__ . '/database/workflow.sqlite';
$schemaPath = __DIR__ . '/database/schema.sql';

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents($schemaPath);
    
    // Split by semicolon via simple regex or just execute all if simple
    // SQLite can execute multiple statements in one go mostly, but let's see.
    // Ideally we executeRaw for the whole file or loop statements.
    
    $pdo->exec($sql);
    
    echo "Database created successfully at $dbPath\n";
    
} catch (PDOException $e) {
    echo "Error creating database: " . $e->getMessage() . "\n";
}
