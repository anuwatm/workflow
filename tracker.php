<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
$newDocId = $_GET['doc_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracker & Statistics - Workflow</title>
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

        /* Sidebar */
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
            position: relative;
        }

        h1,
        h2,
        h3 {
            color: #fff;
            font-weight: 400;
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            border-bottom: 1px solid #333;
            padding-bottom: 10px;
            color: var(--accent-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #aaa;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: #fff;
        }

        .stat-card.blue .stat-value {
            color: #6699ff;
        }

        .stat-card.green .stat-value {
            color: #28a745;
        }

        .stat-card.red .stat-value {
            color: #dc3545;
        }

        .stat-card.yellow .stat-value {
            color: #ffc107;
        }

        /* Metric Icon for System Stats */
        .stat-card.system .stat-value {
            font-size: 2.2rem;
        }

        .metric-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 2.5rem;
            opacity: 0.1;
        }

        .stat-card.system {
            position: relative;
            overflow: hidden;
        }

        /* Table */
        .table-container {
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 50px;
            /* Space before next section */
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: 15px 20px;
            border-bottom: 1px solid #333;
        }

        th {
            background: #222;
            color: #888;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .bg-pending {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }

        .bg-completed {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }

        .bg-rejected {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        .bg-default {
            background: rgba(255, 255, 255, 0.1);
            color: #ccc;
        }

        .doc-no {
            color: var(--accent-color);
            font-weight: 600;
        }

        /* Charts */
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

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 12px;
            width: 600px;
            max-width: 90%;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #444;
            padding-bottom: 15px;
        }

        .modal-header h2 {
            margin: 0;
            color: var(--accent-color);
            font-size: 1.4rem;
        }

        .close-btn {
            background: none;
            border: none;
            color: #aaa;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .close-btn:hover { color: #fff; }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ccc;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            background: #1e1e1e;
            border: 1px solid #444;
            color: #fff;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        .btn-primary {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-primary:hover { background: #4da3ff; }

        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .btn-danger:hover { background: #c82333; }
        
        .del-table { margin-top: 20px; }
        .del-table th { padding: 10px; font-size: 0.75rem;}
        .del-table td { padding: 10px; font-size: 0.85rem;}
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Document System</h2>
        <button class="nav-btn active">📊 Dashboard</button>
        <button class="nav-btn" onclick="location.href='inbox.php'">📥 Inbox</button>
        <button class="nav-btn" onclick="location.href='docFlow.php'">➕ New Document</button>
        <button class="nav-btn" onclick="location.href='flowBilder.php'">⚙️ Builder</button>
        <button class="nav-btn" onclick="location.href='review.php'">👁️ Review Flows</button>
        <button class="nav-btn" onclick="openDelegationModal()">🤝 Delegation</button>
        <div style="flex:1"></div>
        <button class="nav-btn" onclick="location.href='index.php'" style="color:#dc3545">Logout</button>
    </div>

    <!-- Main -->
    <div class="main-content">

        <!-- SECTION 1: MY TRACKER -->
        <div class="section-title">
            <span>My Request Tracker</span>
            <small style="font-size:0.9rem; color:#666;">Personal Overview</small>
        </div>

        <!-- My Stats -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <span class="stat-label">Total Requests</span>
                <span class="stat-value" id="stat-total">0</span>
            </div>
            <div class="stat-card yellow">
                <span class="stat-label">Pending</span>
                <span class="stat-value" id="stat-pending">0</span>
            </div>
            <div class="stat-card green">
                <span class="stat-label">Completed</span>
                <span class="stat-value" id="stat-completed">0</span>
            </div>
            <div class="stat-card red">
                <span class="stat-label">Rejected</span>
                <span class="stat-value" id="stat-rejected">0</span>
            </div>
        </div>

        <!-- My Documents -->
        <div class="table-container">
            <table id="doc-table">
                <thead>
                    <tr>
                        <th>Doc No.</th>
                        <th>Title</th>
                        <th>Amount</th>
                        <th>Workflow</th>
                        <th>Date</th>
                        <th>Current Step</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" style="text-align:center;color:#666;">Loading data...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- SECTION 2: SYSTEM ANALYTICS -->
        <div class="section-title" style="margin-top:50px;">
            <span>Organization Analytics</span>
            <small style="font-size:0.9rem; color:#666;">System-wide Overview</small>
        </div>

        <!-- System Metrics -->
        <div class="stats-grid">
            <div class="stat-card system">
                <span class="stat-label">Total Documents (All Users)</span>
                <span class="stat-value" id="val-docs">0</span>
                <div class="metric-icon">📄</div>
            </div>
            <div class="stat-card system">
                <span class="stat-label">Total Budget Requested</span>
                <span class="stat-value" id="val-amount" style="color:#6699ff">0</span>
                <div class="metric-icon">💰</div>
            </div>
        </div>

        <!-- Charts -->
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

    <!-- Delegation Modal -->
    <div id="delegationModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🤝 Assign Delegation</h2>
                <button class="close-btn" onclick="closeDelegationModal()">&times;</button>
            </div>
            
            <form id="delegationForm" onsubmit="handleDelegationSubmit(event)">
                <div class="form-group">
                    <label>Delegate To (Assignee)</label>
                    <select id="del_delegatee" class="form-control" required>
                        <option value="">Loading users...</option>
                    </select>
                </div>
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex:1">
                        <label>Start Date</label>
                        <input type="datetime-local" id="del_start" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex:1">
                        <label>End Date</label>
                        <input type="datetime-local" id="del_end" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; margin-top: 10px;">Save Delegation</button>
            </form>

            <h3 style="margin-top: 30px; font-size: 1.1rem; border-bottom: 1px solid #444; padding-bottom:10px;">Active & Past Delegations</h3>
            <div style="overflow-x: auto;">
                <table class="del-table" id="delegationTable">
                    <thead>
                        <tr>
                            <th>Assignee</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="5" style="text-align:center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const API = 'api.php';
        // Colors for Charts
        const colors = ['#6699ff', '#28a745', '#dc3545', '#ffc107', '#6f42c1', '#17a2b8'];

        document.addEventListener('DOMContentLoaded', () => {
            loadMyStats();
            loadMyDocuments();
            loadSystemStats();
        });

        // ========================
        // PERSONAL STATS & TABLE
        // ========================
        async function loadMyStats() {
            try {
                const res = await fetch(`${API}?action=get_tracker_stats`);
                const data = await res.json();
                if (data.success && data.stats) {
                    document.getElementById('stat-total').innerText = data.stats.total;
                    document.getElementById('stat-pending').innerText = data.stats.pending;
                    document.getElementById('stat-completed').innerText = data.stats.completed;
                    document.getElementById('stat-rejected').innerText = data.stats.rejected;
                }
            } catch (e) {
                console.error("My Stats Error", e);
            }
        }

        async function loadMyDocuments() {
            try {
                const res = await fetch(`${API}?action=track_documents`);
                const data = await res.json();
                const tbody = document.querySelector('#doc-table tbody');

                if (!data.documents || data.documents.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px;">No documents found.</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                data.documents.forEach(doc => {
                    const tr = document.createElement('tr');
                    let badgeClass = 'bg-default';
                    let statusText = doc.status;

                    if (['START', 'PENDING'].includes(doc.status)) {
                        badgeClass = 'bg-pending';
                        if (doc.status === 'START') statusText = 'Submitted';
                    } else if (doc.status === 'COMPLETED') {
                        badgeClass = 'bg-completed';
                    } else if (doc.status === 'REJECTED') {
                        badgeClass = 'bg-rejected';
                    }

                    const date = new Date(doc.created_at).toLocaleDateString();

                    tr.innerHTML = `
                        <td class="doc-no">${doc.doc_no}</td>
                        <td>${doc.title}</td>
                        <td>${parseFloat(doc.amount).toLocaleString()}</td>
                        <td>${doc.workflow_name || 'Unknown'}</td>
                        <td>${date}</td>
                        <td style="color:#aaa">${doc.current_node || '-'}</td>
                        <td><span class="status-badge ${badgeClass}">${statusText}</span></td>
                    `;
                    tbody.appendChild(tr);
                });

            } catch (e) {
                console.error("Doc Load Error", e);
                document.querySelector('#doc-table tbody').innerHTML = '<tr><td colspan="7" style="text-align:center;color:#ff6666;">Failed to load data</td></tr>';
            }
        }

        // ========================
        // SYSTEM STATISTICS
        // ========================
        async function loadSystemStats() {
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
                console.error("System Stats Error", e);
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
                    maintainAspectRatio: false,
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

            const bgColors = labels.map(l => {
                if (l === 'COMPLETED') return '#28a745';
                if (l === 'REJECTED') return '#dc3545';
                if (l === 'PENDING') return '#ffc107';
                return '#6699ff';
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
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#ccc' } }
                    }
                }
            });
        }

        // ========================
        // DELEGATION LOGIC
        // ========================
        function openDelegationModal() {
            document.getElementById('delegationModal').style.display = 'flex';
            loadDelegationUsers();
            loadMyDelegations();
        }

        function closeDelegationModal() {
            document.getElementById('delegationModal').style.display = 'none';
        }

        async function loadDelegationUsers() {
            try {
                const res = await fetch(`${API}?action=get_users`);
                const data = await res.json();
                const select = document.getElementById('del_delegatee');
                select.innerHTML = '<option value="">Select Colleague...</option>';
                
                if (data.success && data.users) {
                    data.users.forEach(u => {
                        const opt = document.createElement('option');
                        opt.value = u.id;
                        opt.textContent = `${u.username} (Dept: ${u.dept_id})`;
                        select.appendChild(opt);
                    });
                }
            } catch (e) {
                console.error("User Load Error", e);
            }
        }

        async function loadMyDelegations() {
            try {
                const res = await fetch(`${API}?action=get_my_delegations`);
                const data = await res.json();
                const tbody = document.querySelector('#delegationTable tbody');
                
                if (!data.success || !data.delegations || data.delegations.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#888">No active delegations.</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                data.delegations.forEach(d => {
                    const tr = document.createElement('tr');
                    
                    const st = new Date(d.start_date).toLocaleString([], {year: '2-digit', month: 'short', day: '2-digit', hour: '2-digit', minute:'2-digit'});
                    const ed = new Date(d.end_date).toLocaleString([], {year: '2-digit', month: 'short', day: '2-digit', hour: '2-digit', minute:'2-digit'});
                    
                    let statusBadge = d.status === 'ACTIVE' 
                        ? `<span class="status-badge bg-completed">${d.status}</span>` 
                        : `<span class="status-badge bg-rejected">${d.status}</span>`;
                    
                    let actionBtn = d.status === 'ACTIVE' 
                        ? `<button class="btn-danger" onclick="revokeDelegation(${d.id})">Revoke</button>` 
                        : '-';

                    tr.innerHTML = `
                        <td>${d.delegatee_name} <br><small style="color:#aaa">${d.delegatee_position || '-'}</small></td>
                        <td>${st}</td>
                        <td>${ed}</td>
                        <td>${statusBadge}</td>
                        <td>${actionBtn}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (e) {
                console.error("Delegation Load Error", e);
            }
        }

        async function handleDelegationSubmit(e) {
            e.preventDefault();
            const delegatee_id = document.getElementById('del_delegatee').value;
            const start_date = document.getElementById('del_start').value;
            const end_date = document.getElementById('del_end').value;

            if (new Date(start_date) >= new Date(end_date)) {
                alert("End Date must be after Start Date!");
                return;
            }

            const formData = new FormData();
            formData.append('delegatee_id', delegatee_id);
            formData.append('start_date', start_date);
            formData.append('end_date', end_date);

            try {
                const res = await fetch(`${API}?action=save_delegation`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    alert('Delegation saved successfully!');
                    document.getElementById('delegationForm').reset();
                    loadMyDelegations();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (e) {
                alert('Network error occurred.');
            }
        }

        async function revokeDelegation(id) {
            if (!confirm('Are you sure you want to revoke this delegation early?')) return;

            const formData = new FormData();
            formData.append('id', id);

            try {
                const res = await fetch(`${API}?action=revoke_delegation`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    loadMyDelegations();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (e) {
                alert('Network error occurred.');
            }
        }
    </script>
</body>

</html>