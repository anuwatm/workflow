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
    <title>Inbox - Workflow</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
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

        h1, h2, h3 { color: #fff; font-weight: 400; }

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

        /* Table */
        .table-container {
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 50px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
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

        tr:hover { background: rgba(255, 255, 255, 0.02); }

        .doc-no {
            color: var(--accent-color);
            font-weight: 600;
        }

        .action-btn {
            padding: 6px 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: opacity 0.2s;
        }

        .action-btn:hover { opacity: 0.8; }
        .btn-approve { background: #28a745; color: white; margin-right: 5px; }
        .btn-reject { background: #dc3545; color: white; }

        /* Modal */
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7);
            display: none; justify-content: center; align-items: center;
            z-index: 1000;
        }
        .modal-overlay.active { display: flex; }
        .modal-content {
            background: #2a2a2a; border: 1px solid #444;
            padding: 25px; border-radius: 8px; width: 400px;
        }
        .modal-title { font-size: 1.2rem; margin-bottom: 15px; color: #fff; }
        .modal-content textarea {
            width: 100%; padding: 10px; box-sizing: border-box;
            background: rgba(0,0,0,0.2); border: 1px solid #555;
            color: #fff; border-radius: 4px; margin-bottom: 15px;
            font-family: inherit; resize: vertical; min-height: 80px;
        }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; }
        .modal-actions button {
            padding: 8px 16px; border: none; border-radius: 4px;
            cursor: pointer; font-family: inherit;
        }
        .modal-actions .btn-cancel { background: #555; color: white; }
        .modal-actions .btn-confirm { background: var(--accent-color); color: white; }

        .badge-current-node {
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            color: #ccc;
        }

    </style>
</head>

<body>

    <div class="sidebar">
        <h2>Document System</h2>
        <button class="nav-btn active">📥 Inbox</button>
        <button class="nav-btn" onclick="location.href='tracker.php'">📊 Dashboard</button>
        <button class="nav-btn" onclick="location.href='statistics.php'">📈 Statistics</button>
        <div style="height:1px; background:#333; margin:10px 0;"></div>
        <button class="nav-btn" onclick="location.href='docFlow.php'">➕ New Document</button>
        <button class="nav-btn" onclick="location.href='flowBilder.php'">⚙️ Builder</button>
        <div style="flex:1"></div>
        <button class="nav-btn" onclick="location.href='index.php'" style="color:#dc3545">Logout</button>
    </div>

    <div class="main-content">
        <div class="section-title">
            <span>My Pending Approvals</span>
            <small style="font-size:0.9rem; color:#666;">Documents waiting for your action</small>
        </div>

        <div class="table-container">
            <table id="inbox-table">
                <thead>
                    <tr>
                        <th>Doc No.</th>
                        <th>Title</th>
                        <th>Requester</th>
                        <th>Amount</th>
                        <th>Workflow</th>
                        <th>Date</th>
                        <th>Step</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="8" style="text-align:center;color:#666;">Loading data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Action Modal -->
    <div class="modal-overlay" id="action-modal">
        <div class="modal-content">
            <div class="modal-title" id="action-title">Confirm Action</div>
            <input type="hidden" id="action-doc-id">
            <input type="hidden" id="action-type">
            <textarea id="action-remark" placeholder="Optional remark/comment..."></textarea>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn-confirm" id="action-confirm-btn" onclick="confirmAction()">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        const API = 'api.php';

        document.addEventListener('DOMContentLoaded', () => {
            loadInbox();
        });

        async function loadInbox() {
            try {
                const res = await fetch(`${API}?action=get_inbox`);
                const data = await res.json();
                const tbody = document.querySelector('#inbox-table tbody');

                if (!data.success) {
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:#ff6666;">Error: ${data.error}</td></tr>`;
                    return;
                }

                if (!data.documents || data.documents.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:30px;">No pending approvals in your inbox. 🎉</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                data.documents.forEach(doc => {
                    const tr = document.createElement('tr');
                    const date = new Date(doc.created_at).toLocaleDateString();

                    tr.innerHTML = `
                        <td class="doc-no">${doc.doc_no}</td>
                        <td>${doc.title}</td>
                        <td>${doc.requester_name || 'Unknown'}</td>
                        <td>${parseFloat(doc.amount).toLocaleString()}</td>
                        <td>${doc.workflow_name || 'Unknown'}</td>
                        <td>${date}</td>
                        <td><span class="badge-current-node">${doc.current_node || '-'}</span></td>
                        <td>
                            <button class="action-btn btn-approve" onclick="openModal(${doc.id}, 'APPROVE')">Approve</button>
                            <button class="action-btn btn-reject" onclick="openModal(${doc.id}, 'REJECT')">Reject</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

            } catch (e) {
                console.error("Inbox Load Error", e);
                document.querySelector('#inbox-table tbody').innerHTML = '<tr><td colspan="8" style="text-align:center;color:#ff6666;">Failed to load data</td></tr>';
            }
        }

        function openModal(docId, action) {
            document.getElementById('action-doc-id').value = docId;
            document.getElementById('action-type').value = action;
            document.getElementById('action-title').innerText = action === 'APPROVE' ? 'Approve Document' : 'Reject Document';
            
            const btn = document.getElementById('action-confirm-btn');
            btn.style.background = action === 'APPROVE' ? '#28a745' : '#dc3545';
            btn.innerText = action === 'APPROVE' ? 'Submit Approval' : 'Submit Rejection';
            
            document.getElementById('action-remark').value = '';
            document.getElementById('action-modal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('action-modal').classList.remove('active');
        }

        async function confirmAction() {
            const docId = document.getElementById('action-doc-id').value;
            const decision = document.getElementById('action-type').value;
            const remark = document.getElementById('action-remark').value;

            try {
                const formData = new FormData();
                formData.append('doc_id', docId);
                formData.append('decision', decision);
                formData.append('remark', remark);

                const res = await fetch(`${API}?action=process_document`, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await res.json();
                
                if (data.success) {
                    closeModal();
                    loadInbox(); // reload table
                    
                    // Show a simple alert or toast
                    alert(`Document successfully ${decision.toLowerCase()}d!`);
                } else {
                    alert('Error: ' + data.error);
                }

            } catch (e) {
                console.error("Action Error", e);
                alert('An error occurred while processing the request.');
            }
        }
    </script>
</body>
</html>
