<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Workflow</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            height: 100vh;
            display: flex;
            overflow: hidden;
            color: #eee;
        }

        /* Sidebar Reuse */
        .sidebar {
            width: 250px;
            background: #1e1e1e;
            border-right: 1px solid #333;
            display: flex;
            flex-direction: column;
            padding: 20px;
            gap: 10px;
        }

        .sidebar h2 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--accent-color);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .nav-btn {
            background: transparent;
            border: none;
            color: #aaa;
            text-align: left;
            padding: 12px;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-btn:hover,
        .nav-btn.active {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .nav-btn.active {
            border-left: 3px solid var(--accent-color);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        /* Metric Cards */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 12px;
            padding: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .metric-info h3 {
            margin: 0;
            font-size: 0.9rem;
            color: #aaa;
            font-weight: 400;
        }

        .metric-info .value {
            font-size: 2.2rem;
            font-weight: 600;
            color: #fff;
            margin-top: 5px;
        }

        .metric-icon {
            font-size: 2.5rem;
            opacity: 0.2;
        }

        /* Charts Area */
        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .chart-panel {
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 12px;
            padding: 20px;
            min-height: 350px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .chart-panel h3 {
            margin-top: 0;
            color: #ddd;
            width: 100%;
            text-align: left;
            font-size: 1rem;
            margin-bottom: 20px;
        }

        .chart-wrapper {
            width: 100%;
            height: 100%;
            position: relative;
        }

        @media (max-width: 900px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Analytics</h2>
        <button class="nav-btn" onclick="location.href='tracker.php'">📊 Tracker</button>
        <button class="nav-btn active">📈 Statistics</button>
        <button class="nav-btn" onclick="location.href='inbox.php'">📥 Inbox</button>
        <div style="height:1px; background:#333; margin:10px 0;"></div>
        <button class="nav-btn" onclick="location.href='flowBilder.php'">⚙️ Builder</button>
        <button class="nav-btn" onclick="location.href='docFlow.php'">➕ New Document</button>
        <div style="flex:1"></div>
        <button class="nav-btn" onclick="location.href='index.php'" style="color:#dc3545">Logout</button>
    </div>

    <div class="main-content">
        <h1>Dashboard Overview</h1>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-info">
                    <h3>Total Documents</h3>
                    <div class="value" id="val-docs">0</div>
                </div>
                <div class="metric-icon">📄</div>
            </div>
            <div class="metric-card">
                <div class="metric-info">
                    <h3>Total Budget Requested</h3>
                    <div class="value" id="val-amount">0</div>
                </div>
                <div class="metric-icon" style="color:#6699ff; opacity:0.5;">💰</div>
            </div>
        </div>

        <div class="charts-container">
            <div class="chart-panel">
                <h3>Documents by Department</h3>
                <div class="chart-wrapper">
                    <canvas id="deptChart"></canvas>
                </div>
            </div>
            <div class="chart-panel">
                <h3>Workflow Status Distribution</h3>
                <div class="chart-wrapper">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API = 'api.php';

        // Colors for Charts
        const colors = ['#6699ff', '#28a745', '#dc3545', '#ffc107', '#6f42c1', '#17a2b8'];

        document.addEventListener('DOMContentLoaded', loadData);

        async function loadData() {
            try {
                const res = await fetch(`${API}?action=get_statistics`);
                const data = await res.json();

                if (data.success && data.stats) {
                    const s = data.stats;

                    // Metrics
                    document.getElementById('val-docs').innerText = s.total_docs;
                    document.getElementById('val-amount').innerText = '$ ' + parseFloat(s.total_amount).toLocaleString();

                    // Charts
                    initDeptChart(s.by_dept);
                    initStatusChart(s.by_status);
                }
            } catch (e) {
                console.error("Stats Error", e);
            }
        }

        function initDeptChart(data) {
            const ctx = document.getElementById('deptChart').getContext('2d');
            const labels = data.map(d => d.name);
            const values = data.map(d => d.count);

            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderColor: '#2a2a2a',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#ccc' } }
                    }
                }
            });
        }

        function initStatusChart(data) {
            const ctx = document.getElementById('statusChart').getContext('2d');
            const labels = data.map(d => d.status);
            const values = data.map(d => d.count);

            // Map status to specific colors if possible
            const bgColors = labels.map(l => {
                if (l === 'COMPLETED') return '#28a745';
                if (l === 'REJECTED') return '#dc3545';
                if (l === 'PENDING') return '#ffc107';
                return '#6699ff'; // Default
            });

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: bgColors,
                        borderColor: '#2a2a2a',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#ccc' } }
                    }
                }
            });
        }
    </script>
</body>

</html>