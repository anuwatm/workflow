<?php
session_start();
$docId = $_GET['doc_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Document Tracker</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            padding: 50px;
            text-align: center;
            color: #eee;
        }

        .card {
            background: #2a2a2a;
            border: 1px solid #444;
            padding: 40px;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 12px;
        }

        h1 {
            margin-bottom: 20px;
        }

        .status {
            font-size: 24px;
            color: #4caf50;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>Document Tracker</h1>
        <p>Tracking Document ID: <strong>
                <?php echo htmlspecialchars($docId); ?>
            </strong></p>
        <div class="status">Submitted Successfully!</div>
        <p>Current Status: <strong>PENDING REVIEW</strong></p>
        <br>
        <a href="docFlow.php" style="color:#6699ff">Submit Another Document</a> |
        <a href="flowBilder.php" style="color:#6699ff">Back to Builder</a>
    </div>
</body>

</html>