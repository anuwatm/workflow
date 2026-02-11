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
    <title>Review Workflow</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .review-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        .sidebar {
            width: 300px;
            background: #1e1e1e;
            border-right: 1px solid #333;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #333;
            font-size: 18px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-back {
            background: #444;
            border: none;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .workflow-list {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
        }

        .workflow-item {
            padding: 15px;
            margin-bottom: 10px;
            background: #2a2a2a;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .workflow-item:hover {
            background: #333;
        }

        .workflow-item.active {
            border-color: var(--accent-color);
            background: #333;
        }

        .wf-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .wf-meta {
            font-size: 12px;
            color: #888;
        }

        .main-content {
            flex: 1;
            padding: 40px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Stepper Styles */
        .stepper-wrapper {
            max-width: 800px;
            width: 100%;
            position: relative;
        }

        .step-item {
            display: flex;
            margin-bottom: 30px;
            position: relative;
        }

        /* Line connecting steps */
        .step-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 20px;
            /* Center with circle */
            top: 40px;
            bottom: -30px;
            /* Connect to next */
            width: 2px;
            background: #444;
            z-index: 0;
        }

        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #333;
            border: 2px solid #555;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            z-index: 1;
            flex-shrink: 0;
            margin-right: 20px;
        }

        .step-content {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 8px;
            flex: 1;
            border: 1px solid #444;
        }

        /* Node Type Colors */
        .type-start {
            border-color: #28a745;
            color: #28a745;
        }

        .type-end {
            border-color: #dc3545;
            color: #dc3545;
        }

        .type-human {
            border-color: #007bff;
            color: #007bff;
        }

        .type-logic {
            border-color: #ffc107;
            color: #ffc107;
        }

        .type-system {
            border-color: #6c757d;
            color: #adb5bd;
        }

        .step-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }

        .step-type-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            background: rgba(0, 0, 0, 0.3);
            text-transform: uppercase;
        }

        .step-desc {
            font-size: 14px;
            color: #ccc;
            margin-bottom: 10px;
        }

        .step-widgets {
            font-size: 13px;
            color: #888;
            background: rgba(0, 0, 0, 0.2);
            padding: 10px;
            border-radius: 4px;
        }

        .branch-container {
            margin-left: 20px;
            margin-top: 10px;
            border-left: 2px dashed #555;
            padding-left: 20px;
        }

        .branch-label {
            font-size: 12px;
            color: #aaa;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="review-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <span>Saved Workflows</span>
                <button class="btn-back" onclick="location.href='flowBilder.php'">Back</button>
            </div>
            <div id="workflow-list" class="workflow-list">
                <!-- Items injected here -->
                <div style="padding:20px;color:#888;">Loading...</div>
            </div>
        </div>

        <div class="main-content">
            <h2 id="current-wf-title" style="margin-bottom:10px;display:none;">Workflow Title</h2>
            <div id="current-wf-desc" style="margin-bottom:30px;color:#888;display:none;"></div>

            <div id="stepper-container" class="stepper-wrapper">
                <div style="text-align:center;color:#666;margin-top:100px;">
                    Select a workflow to review its steps.
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            fetchWorkflows();
        });

        async function fetchWorkflows() {
            try {
                const res = await fetch('api.php?action=list');
                const data = await res.json();
                const list = document.getElementById('workflow-list');

                if (!data.files || data.files.length === 0) {
                    list.innerHTML = '<div style="padding:20px;">No workflows found.</div>';
                    return;
                }

                list.innerHTML = '';
                data.files.forEach(file => {
                    const div = document.createElement('div');
                    div.className = 'workflow-item';
                    div.innerHTML = `
                    <div class="wf-title">${file.name}</div>
                    <div class="wf-meta">${new Date(file.updated_at).toLocaleDateString()}</div>
                `;
                    div.onclick = () => loadWorkflow(file.name, div);
                    list.appendChild(div);
                });

            } catch (e) {
                console.error(e);
            }
        }

        async function loadWorkflow(name, element) {
            // Highlight active
            document.querySelectorAll('.workflow-item').forEach(e => e.classList.remove('active'));
            if (element) element.classList.add('active');

            try {
                const res = await fetch('api.php?action=load&file=' + encodeURIComponent(name));
                const data = await res.json();

                if (data.content) {
                    document.getElementById('current-wf-title').innerText = data.meta.name;
                    document.getElementById('current-wf-title').style.display = 'block';
                    document.getElementById('current-wf-desc').innerText = data.meta.description || '';
                    document.getElementById('current-wf-desc').style.display = 'block';

                    renderStepper(data.content);
                }
            } catch (e) {
                console.error("Failed to load", e);
                alert("Failed to load workflow data");
            }
        }

        function renderStepper(workflowData) {
            const container = document.getElementById('stepper-container');
            container.innerHTML = '';

            const nodes = workflowData.nodes || [];
            const connections = workflowData.connections || [];

            // Map nodes
            const nodeMap = {};
            nodes.forEach(n => nodeMap[n.id] = n);

            // Find start node
            const startNode = nodes.find(n => n.type === 'StartFlow');
            if (!startNode) {
                container.innerHTML = '<div style="color:red">Invalid Workflow: No Start Node found.</div>';
                return;
            }

            // Traversal Set to avoid loops
            const visited = new Set();

            // Recursive render function
            function renderStep(nodeId, parentElement) {
                if (visited.has(nodeId)) return; // Avoid cycles for simple view
                visited.add(nodeId);

                const node = nodeMap[nodeId];
                if (!node) return;

                const stepEl = document.createElement('div');
                stepEl.className = 'step-item';

                // Icon/Number
                const circle = document.createElement('div');
                circle.className = 'step-circle';
                // Simple icon logic
                let icon = '•';
                let typeClass = 'type-system';
                if (node.type === 'StartFlow') { icon = 'S'; typeClass = 'type-start'; }
                else if (node.type === 'EndFlow') { icon = 'E'; typeClass = 'type-end'; }
                else if (node.category === 'human') { icon = 'User'; typeClass = 'type-human'; }
                else if (node.category === 'logic') { icon = '?'; typeClass = 'type-logic'; }

                circle.classList.add(typeClass);
                circle.innerText = icon;

                // Content
                const content = document.createElement('div');
                content.className = 'step-content';

                // Title & Type
                const titleHtml = `
                <div class="step-title">
                    ${node.type} 
                    <span class="step-type-badge ${typeClass}">${node.category || 'System'}</span>
                </div>`;

                // Details (Widgets)
                let detailsHtml = '';
                if (node.widgets_values && Object.keys(node.widgets_values).length > 0) {
                    detailsHtml = '<div class="step-widgets">';
                    Object.entries(node.widgets_values).forEach(([key, val]) => {
                        detailsHtml += `<div><strong>${key}:</strong> ${val}</div>`;
                    });
                    detailsHtml += '</div>';
                }

                content.innerHTML = titleHtml + detailsHtml;

                stepEl.appendChild(circle);
                stepEl.appendChild(content);
                parentElement.appendChild(stepEl);

                // Find next nodes
                // Logic: 
                // 1. Find all connections OUT from this node
                // 2. If simple (1 output), just continue
                // 3. If branching (Condition/Review), show branches

                const outputs = connections.filter(c => c.output_node_id === nodeId);

                if (outputs.length === 0) return; // End of branch

                // Group by output socket name (e.g., TRUE/FALSE, PASSED/REJECTED)
                const branches = {};
                outputs.forEach(c => {
                    if (!branches[c.output_name]) branches[c.output_name] = [];
                    branches[c.output_name].push(c.input_node_id);
                });

                const branchKeys = Object.keys(branches);

                if (branchKeys.length === 1 && branchKeys[0] === 'start' || branchKeys[0] === 'DONE') {
                    // Linear flow, direct connection
                    branches[branchKeys[0]].forEach(nextId => renderStep(nextId, parentElement));
                } else {
                    // Branching flow
                    // We need to append branch containers to the CURRENT content or a new wrapper
                    const branchWrapper = document.createElement('div');
                    branchWrapper.style.marginTop = "10px";

                    branchKeys.forEach(key => {
                        const bContainer = document.createElement('div');
                        bContainer.className = 'branch-container';
                        bContainer.innerHTML = `<div class="branch-label">Path: ${key}</div>`;

                        branches[key].forEach(nextId => {
                            // We create a mini-container for the branch steps
                            const subSteps = document.createElement('div');
                            renderStep(nextId, subSteps);
                            bContainer.appendChild(subSteps);
                        });

                        branchWrapper.appendChild(bContainer);
                    });

                    content.appendChild(branchWrapper);
                }
            }

            // Start rendering
            renderStep(startNode.id, container);
        }
    </script>

</body>

</html>