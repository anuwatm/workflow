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
    <title>Create Document - Workflow</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
            --glass-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --text-glow: 0 0 10px rgba(118, 75, 162, 0.5);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            color: #eee;
            height: 100vh;
            overflow: hidden;
            margin: 0;
            display: flex;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            display: flex;
            width: 100%;
            height: 100%;
            padding: 20px;
            gap: 20px;
        }

        /* Glassmorphism Panels */
        .panel {
            background: key-frames;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            box-shadow: var(--glass-shadow);
            padding: 30px;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .panel:hover {
            box-shadow: 0 8px 32px 0 rgba(118, 75, 162, 0.2);
        }

        /* LAYOUT ADJUSTMENT: 3/4 Left, 1/4 Right */
        .left-panel {
            flex: 3; 
            min-width: 600px;
            overflow-y: auto;
        }

        .right-panel {
            flex: 1;
            min-width: 300px;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Typography */
        h2 {
            font-weight: 600;
            margin-bottom: 20px;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0px 0px 20px rgba(118, 75, 162, 0.3);
            font-size: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #aaa;
            font-size: 0.9em;
            font-weight: 300;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--glass-border);
            border-radius: 8px;
            color: #fff;
            font-family: inherit;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #764ba2;
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 0 10px rgba(118, 75, 162, 0.3);
        }

        /* File Upload */
        .drop-zone {
            border: 2px dashed var(--glass-border);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            background: rgba(0,0,0,0.1);
        }

        .drop-zone:hover, .drop-zone.dragover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .drop-zone-text {
            color: #aaa;
            font-size: 0.9em;
            pointer-events: none;
        }

        .file-list-display {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .file-item {
            background: rgba(255,255,255,0.1);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85em;
            display: flex;
            justify-content: space-between;
        }

        /* Buttons */
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(118, 75, 162, 0.5);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid var(--glass-border);
            color: #ccc;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.05);
            color: #fff;
        }

        /* USER PROFILE CARD */
        .profile-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 8px 32px 0 rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
            position: relative;
            z-index: 10;
        }

        .profile-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--primary-gradient);
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: bold;
            color: white;
            box-shadow: 0 4px 15px rgba(118, 75, 162, 0.5);
            border: 2px solid rgba(255,255,255,0.2);
        }

        .profile-name {
            font-size: 1.2em;
            font-weight: 600;
            color: #fff;
            margin-bottom: 4px;
        }

        .profile-meta {
            font-size: 0.9em;
            color: #bbb;
            margin-bottom: 12px;
        }

        .profile-date {
            font-size: 0.8em;
            color: #eee;
            background: rgba(118, 75, 162, 0.4);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            border: 1px solid rgba(255,255,255,0.1);
        }


        /* Workflow Preview (Stepper) */
        .preview-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Start from top */
            padding: 20px;
            overflow: auto;
            position: relative;
        }
        
        .timeline-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            width: 100%;
        }

        .tl-node {
            background: var(--node-bg);
            border: 1px solid var(--node-border);
            border-radius: 12px;
            padding: 10px; /* Smaller padding */
            width: 100%; /* Full width of container */
            max-width: 220px;
            text-align: center;
            position: relative;
            margin-bottom: 30px;
            z-index: 2;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            transition: transform 0.2s;
        }
        
        .tl-node:hover {
            transform: scale(1.02);
            border-color: var(--accent-color);
        }

        .tl-icon { font-size: 20px; margin-bottom: 4px; display: block; }
        .tl-title { font-weight: 600; color: #fff; margin-bottom: 2px; font-size: 0.9em; }
        .tl-desc { font-size: 0.75em; color: #aaa; }

        .tl-line {
            position: absolute;
            width: 2px;
            background: linear-gradient(to bottom, var(--accent-color), #444);
            top: 0;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            z-index: 0;
        }
        
        .empty-preview {
            text-align: center;
            color: #666;
            padding: 20px;
            border: 1px dashed #444;
            border-radius: 12px;
            width: 100%;
            font-size: 0.9em;
        }

    </style>
</head>
<body>

    <div class="container">
        <!-- Left Panel: Form (3/4 Width) -->
        <div class="panel left-panel">
            <h2>New Document Flow</h2>
            
            <form id="doc-form">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- 1. Workflow Selection -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label for="workflow-select">Select Workflow</label>
                        <select id="workflow-select" required onchange="handleWorkflowChange()">
                            <option value="" disabled selected>-- Choose a Workflow --</option>
                        </select>
                        <div id="workflow-desc" style="font-size:0.85em; color:#888; margin-top:5px; padding-left:5px;"></div>
                    </div>

                    <!-- 2. Document Details -->
                    <div class="form-group">
                        <label for="doc-title">Document Title</label>
                        <input type="text" id="doc-title" placeholder="e.g. Budget Approval Q3" required>
                    </div>

                    <div class="form-group">
                        <label for="doc-amount">Amount</label>
                        <input type="number" id="doc-amount" placeholder="0.00" step="0.01" required>
                    </div>

                    <div class="form-group" style="grid-column: span 2;">
                        <label for="doc-dept">Origin Department</label>
                        <select id="doc-dept" required>
                            <option value="" disabled selected>-- Select Department --</option>
                        </select>
                    </div>
                </div>

                <hr style="border:0; border-top:1px solid var(--glass-border); margin: 20px 0;">

                <!-- 3. File Upload -->
                <div class="form-group">
                    <label>Attachments</label>
                    <div class="drop-zone" id="drop-zone">
                        <div class="drop-zone-text">Drag & Drop files here or Click to Browse</div>
                        <input type="file" id="file-input" multiple style="display:none;">
                    </div>
                    <div id="file-list" class="file-list-display"></div>
                </div>

                <!-- Action Buttons -->
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="clearForm()">Clear</button>
                    <button type="submit" class="btn btn-primary">Start Workflow</button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='flowBilder.php'">Back</button>
                </div>
            </form>
        </div>

        <!-- Right Panel: Preview (1/4 Width) -->
        <div class="panel right-panel">
            
            <!-- User Profile Card -->
            <div class="profile-card">
                <div class="profile-avatar" id="req-avatar">👤</div>
                <div class="profile-name" id="req-name">Loading...</div>
                <div class="profile-meta" id="req-meta">...</div>
                <div class="profile-date" id="req-date">...</div>
            </div>

            <div style="font-weight:600; color:#888; margin-bottom:10px; font-size:0.9em; text-transform:uppercase; letter-spacing:1px; border-bottom:1px solid #333; padding-bottom:5px;">Live Preview</div>
            
            <div class="preview-container" id="preview-container">
                <div class="empty-preview">
                    Select a workflow to see timeline.
                </div>
            </div>

            <!-- Decorative Gradients -->
            <div style="position:absolute; bottom:-20px; right:-20px; width:150px; height:150px; background:radial-gradient(circle, rgba(118,75,162,0.1) 0%, transparent 70%); pointer-events:none;"></div>
        </div>
    </div>

<script>
    // Constants
    const API_URL = 'api.php';
    let currentFiles = [];

    // Init
    document.addEventListener('DOMContentLoaded', async () => {
        await fetchMetaData();
        await fetchUserInfo();
        setupDragAndDrop();
        
        document.getElementById('doc-form').addEventListener('submit', handleStartWorkflow);
    });

    async function fetchMetaData() {
        try {
            // Fetch Workflows
            const flowRes = await fetch(`${API_URL}?action=list`);
            const flowData = await flowRes.json();
            const wfSelect = document.getElementById('workflow-select');
            
            if (flowData.files) {
                flowData.files.forEach(f => {
                    const opt = document.createElement('option');
                    opt.value = f.id; 
                    opt.dataset.filename = f.name;
                    opt.innerText = f.name;
                    opt.dataset.desc = f.description || 'No description.';
                    wfSelect.appendChild(opt);
                });
            }

            // Fetch Departments
            const metaRes = await fetch(`${API_URL}?action=get_meta_data`);
            const metaData = await metaRes.json();
            const deptSelect = document.getElementById('doc-dept');
            
            if (metaData.departments) {
                metaData.departments.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.innerText = d.name;
                    deptSelect.appendChild(opt);
                });
            }
        } catch (e) {
            console.error("Init Error", e);
        }
    }

    async function fetchUserInfo() {
        try {
            const res = await fetch(`${API_URL}?action=get_user_details`);
            const data = await res.json();
            
            if (data.success) {
                const username = data.username || 'User';
                document.getElementById('req-name').innerText = username;
                document.getElementById('req-avatar').innerText = username.charAt(0).toUpperCase();
                document.getElementById('req-meta').innerText = `${data.position || 'Staff'} • ${data.department || 'General'}`;
                
                const now = new Date();
                // Format: 11 Feb 2026
                const options = { day: 'numeric', month: 'short', year: 'numeric' };
                document.getElementById('req-date').innerText = now.toLocaleDateString('en-GB', options);
            }
        } catch (e) {
            console.error("User Info Error", e);
        }
    }

    // Workflow Selection Change
    async function handleWorkflowChange() {
        const select = document.getElementById('workflow-select');
        const selectedOpt = select.options[select.selectedIndex];
        
        if (!selectedOpt.value) return;

        // 1. Show Desc
        document.getElementById('workflow-desc').innerText = selectedOpt.dataset.desc;

        // 2. Fetch Visual Data
        const filename = selectedOpt.dataset.filename;
        try {
            const res = await fetch(`${API_URL}?action=load&file=` + encodeURIComponent(filename));
            const data = await res.json();
            if (data.content) {
                renderTimeline(data.content);
            }
        } catch (e) {
            console.error("Load Flow Error", e);
        }
    }

    function renderTimeline(workflowData) {
        const container = document.getElementById('preview-container');
        container.innerHTML = '<div class="timeline-wrapper" id="timeline-wrapper"></div>';
        const wrapper = document.getElementById('timeline-wrapper');

        const nodes = workflowData.nodes || [];
        const connections = workflowData.connections || [];
        const nodeMap = {};
        nodes.forEach(n => nodeMap[n.id] = n);
        const startNode = nodes.find(n => n.type === 'StartFlow');

        if (!startNode) return;

        const visited = new Set();
        
        function renderNode(nodeId) {
            if (visited.has(nodeId)) return;
            visited.add(nodeId);
            const node = nodeMap[nodeId];
            if(!node) return;

            let icon = '⚙️';
            let label = node.type;
            let sub = node.category || 'System';
            
            if (node.type === 'StartFlow') { icon = '🚀'; label = 'Start'; }
            if (node.type === 'OfficerReview') { icon = '👮'; label = 'Officer Review'; sub = 'Human Check'; }
            if (node.type === 'ManagerApproval') { icon = '👩‍💼'; label = 'Manager Approval'; sub = 'Final Decision'; }
            if (node.type === 'EndFlow') { icon = '🏁'; label = 'End'; sub = 'Completed'; }

            const el = document.createElement('div');
            el.className = 'tl-node';
            el.innerHTML = `
                <div class="tl-icon">${icon}</div>
                <div class="tl-title">${label}</div>
                <div class="tl-desc">${sub}</div>
            `;
            wrapper.appendChild(el);

            const outputs = connections.filter(c => c.output_node_id === nodeId);
            if (outputs.length > 0) {
                 // Line logic could be improved, but sufficient for vertical stack
            }

            const targets = [...new Set(outputs.map(c => c.input_node_id))];
            targets.forEach(tId => renderNode(tId));
        }

        renderNode(startNode.id);
    }

    // Drag & Drop
    function setupDragAndDrop() {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');

        dropZone.onclick = () => fileInput.click();

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', () => handleFiles(fileInput.files));
    }

    function handleFiles(files) {
        const list = document.getElementById('file-list');
        for (let file of files) {
            currentFiles.push(file);
            const item = document.createElement('div');
            item.className = 'file-item';
            item.innerHTML = `<span>${file.name}</span> <span style="cursor:pointer;color:#ff6666" onclick="removeFile(this, '${file.name}')">✕</span>`;
            list.appendChild(item);
        }
    }

    function removeFile(el, name) {
        currentFiles = currentFiles.filter(f => f.name !== name);
        el.parentElement.remove();
    }

    function clearForm() {
        document.getElementById('doc-form').reset();
        document.getElementById('file-list').innerHTML = '';
        document.getElementById('preview-container').innerHTML = '<div class="empty-preview">Select a workflow...</div>';
        currentFiles = [];
    }

    // Submit
    async function handleStartWorkflow(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('title', document.getElementById('doc-title').value);
        formData.append('amount', document.getElementById('doc-amount').value);
        formData.append('dept_id', document.getElementById('doc-dept').value);
        formData.append('workflow_id', document.getElementById('workflow-select').value);
        
        currentFiles.forEach(f => formData.append('files[]', f));

        try {
            const res = await fetch(`${API_URL}?action=start_document`, {
                method: 'POST',
                body: formData
            });
            
            const result = await res.json();
            
            if (result.success) {
                window.location.href = `tracker.php?doc_id=${result.doc_id}`;
            } else {
                alert('Error: ' + result.error);
            }

        } catch (err) {
            console.error(err);
            alert('Failed to start workflow');
        }
    }

</script>
</body>
</html>