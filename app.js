// Node Definitions - Document Approval Workflow
const NodeRegistry = {
    'StartFlow': {
        title: 'Start Flow',
        category: 'start-end',
        outputs: [{ name: 'start', type: 'FLOW' }],
        widgets: []
    },
    'OfficerReview': {
        title: 'Review',
        category: 'human',
        inputs: [{ name: 'INPUT', type: 'FLOW' }],
        outputs: [
            { name: 'PASSED', type: 'FLOW' },
            { name: 'REJECTED', type: 'FLOW' }
        ],
        widgets: [
            { type: 'select', name: 'Review', options: [] }, // Options populated dynamically
            { type: 'text', name: 'remark', value: '' }
        ]
    },
    'ManagerApproval': {
        title: 'Approval',
        category: 'human',
        inputs: [{ name: 'INPUT', type: 'FLOW' }],
        outputs: [
            { name: 'APPROVED', type: 'FLOW' },
            { name: 'DENIED', type: 'FLOW' }
        ],
        widgets: [
            { type: 'select', name: 'level', options: ['Supervisor', 'Manager', 'Director'] },
            { type: 'number', name: 'threshold', value: 5000 }
        ]
    },
    'Condition': {
        title: 'Condition (Wait/Check)',
        category: 'logic',
        inputs: [{ name: 'EXEC', type: 'FLOW' }, { name: 'DATA', type: 'DATA' }],
        outputs: [
            { name: 'TRUE', type: 'FLOW' },
            { name: 'FALSE', type: 'FLOW' }
        ],
        widgets: [
            { type: 'select', name: 'field', options: ['Amount', 'Department', 'Urgency'] },
            { type: 'select', name: 'operator', options: ['>', '<', '=', '!='] },
            { type: 'text', name: 'value', value: '1000' }
        ]
    },
    'SystemAction': {
        title: 'System Action',
        category: 'system',
        inputs: [{ name: 'EXEC', type: 'FLOW' }, { name: 'DATA', type: 'DATA' }],
        outputs: [{ name: 'DONE', type: 'FLOW' }],
        widgets: [
            { type: 'select', name: 'action', options: ['Send Email', 'Update Database', 'Generate PDF', 'Call API'] },
            { type: 'text', name: 'recipient', value: 'user@example.com' }
        ]
    },
    'EndFlow': {
        title: 'End Flow',
        category: 'start-end',
        inputs: [{ name: 'INPUT', type: 'FLOW' }],
        widgets: [
            { type: 'select', name: 'status', options: ['Completed', 'Terminated', 'Archived'] }
        ]
    }
};

class App {
    constructor() {
        this.nodes = [];
        this.connections = [];
        this.canvas = document.getElementById('app');
        this.nodesContainer = document.getElementById('nodes-container');
        this.connectionsLayer = document.getElementById('connections-layer');
        this.tempConnectionsLayer = document.getElementById('temp-connections-layer');
        this.titleElement = document.getElementById('workflow-title');
        this.contextMenu = new ContextMenu(this);
        this.scale = 1;
        this.panX = 0;
        this.panY = 0;
        this.isPanning = false;
        this.draggedNode = null;
        this.draggedConnection = null;
        this.dragStartX = 0;
        this.dragStartY = 0;
        this.selectedConnection = null;
        this.currentMeta = { name: '', description: '' };

        // Cache Menu Info Elements
        this.menuInfoPanel = document.getElementById('workflow-info-panel');
        this.menuInfoEmpty = document.getElementById('workflow-info-empty');
        this.menuName = document.getElementById('menu-workflow-name');
        this.menuOwner = document.getElementById('menu-workflow-owner');
        this.menuDate = document.getElementById('menu-workflow-date');
        this.menuDesc = document.getElementById('menu-workflow-desc');

        this.init();
    }

    async init() {
        await this.fetchMetaData();
        this.setupEventListeners();
        // Add specific event listener for delete key
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Delete' || e.key === 'Backspace') {
                if (this.selectedConnection) {
                    this.showConfirmModal("Delete selected connection?", () => {
                        this.deleteConnection(this.selectedConnection);
                    });
                }
            }
        });
    }

    showNotification(message, type = 'info') {
        const container = document.getElementById('notification-area');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `notification-toast ${type}`;
        toast.innerText = message;

        container.appendChild(toast);

        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    showConfirmModal(message, onConfirm) {
        const overlay = document.getElementById('modal-overlay');
        const text = document.getElementById('modal-text');
        const confirmBtn = document.getElementById('modal-confirm-btn');
        const cancelBtn = document.getElementById('modal-cancel-btn');

        if (!overlay || !text || !confirmBtn || !cancelBtn) return;

        text.innerText = message;
        overlay.classList.remove('hidden');

        const close = () => {
            overlay.classList.add('hidden');
            confirmBtn.onclick = null;
            cancelBtn.onclick = null;
        };

        confirmBtn.onclick = () => {
            onConfirm();
            close();
        };

        cancelBtn.onclick = close;

        // Close on clicking outside
        overlay.onclick = (e) => {
            if (e.target === overlay) close();
        };
    }

    setupEventListeners() {
        // Context Menu
        this.canvas.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.contextMenu.show(e.clientX, e.clientY);
        });

        // Hide Context Menu on click
        window.addEventListener('click', () => {
            this.contextMenu.hide();
        });

        // Panning Canvas
        this.canvas.addEventListener('mousedown', (e) => {
            // Only pan if clicking on background
            if (e.target === this.canvas || e.target.id === 'grid' || e.target.id === 'connections-layer') {
                this.isPanning = true;
                this.dragStartX = e.clientX;
                this.dragStartY = e.clientY;
                this.initialPanX = this.panX;
                this.initialPanY = this.panY;
                this.canvas.style.cursor = 'grabbing';
                this.contextMenu.hide();

                // Deselect connection
                this.selectedConnection = null;
                this.updateConnections();
            }
        });

        window.addEventListener('mousemove', (e) => {
            if (this.isPanning) {
                const dx = e.clientX - this.dragStartX;
                const dy = e.clientY - this.dragStartY;
                this.panX = this.initialPanX + dx;
                this.panY = this.initialPanY + dy;
                this.updateTransform();
            }

            if (this.draggedNode) {
                const dx = (e.clientX - this.dragStartX) / this.scale;
                const dy = (e.clientY - this.dragStartY) / this.scale;
                this.draggedNode.x = this.draggedNode.initialX + dx;
                this.draggedNode.y = this.draggedNode.initialY + dy;
                this.draggedNode.updateElementPosition();
                this.updateConnections();
            }

            if (this.draggedConnection) {
                /* Corrected logic for mouse tracking in transformed space */
                const rect = this.canvas.getBoundingClientRect();
                const mouseX = (e.clientX - rect.left - this.panX) / this.scale;
                const mouseY = (e.clientY - rect.top - this.panY) / this.scale;

                this.updateTempConnection(mouseX, mouseY);
            }
        });

        window.addEventListener('mouseup', (e) => {
            this.isPanning = false;
            this.draggedNode = null;
            this.canvas.style.cursor = 'default';

            if (this.draggedConnection) {

                // Check if dropped on a valid socket (checking class list safely)
                if (e.target && e.target.classList && e.target.classList.contains('socket')) {
                    const targetSocket = e.target;
                    const targetNodeId = targetSocket.dataset.nodeId;
                    const targetSocketType = targetSocket.dataset.type; // 'input' or 'output'

                    // Validate connection (Output -> Input)
                    if (this.draggedConnection.type !== targetSocketType) {
                        // Create permanent connection
                        this.createConnection(this.draggedConnection, {
                            nodeId: targetNodeId,
                            socketName: targetSocket.dataset.name,
                            type: targetSocketType,
                            element: targetSocket
                        });
                    }
                }

                // Clear temp connection
                this.draggedConnection = null;
                const tempPath = document.getElementById('temp-connection');
                if (tempPath) tempPath.remove();
            }
        });

        // Zooming - Fixed logic for mouse-centered zoom
        this.canvas.addEventListener('wheel', (e) => {
            e.preventDefault();
            const zoomSensitivity = 0.001;
            const delta = -e.deltaY * zoomSensitivity;
            const newScale = Math.min(Math.max(0.1, this.scale + delta), 5);

            const rect = this.canvas.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            // Calculate new pan to keep mouse grounded
            this.panX = mouseX - (mouseX - this.panX) * (newScale / this.scale);
            this.panY = mouseY - (mouseY - this.panY) * (newScale / this.scale);

            this.scale = newScale;
            this.updateTransform();
        });

        // Buttons
        const btnStartFlow = document.getElementById('btn-start-flow');
        if (btnStartFlow) {
            btnStartFlow.addEventListener('click', () => {
                this.showConfirmModal('This will clear the current workflow. Are you sure?', () => {
                    try {
                        this.clear();

                        // Add Start Flow Node
                        // Ensure StartFlow is defined
                        if (!NodeRegistry['StartFlow']) throw new Error("StartFlow definition missing");
                        const startNode = this.addNode('StartFlow', 50, 200);

                        // Add End Flow Node
                        if (!NodeRegistry['EndFlow']) throw new Error("EndFlow definition missing");
                        const endNode = this.addNode('EndFlow', 600, 200);

                        this.showNotification('New workflow started', 'success');
                    } catch (err) {
                        console.error(err);
                        this.showNotification('Error starting flow: ' + err.message, 'error');
                    }
                });
            });
        }

        const btnRun = document.getElementById('btn-run');
        if (btnRun) btnRun.addEventListener('click', () => this.runWorkflow());

        const btnSave = document.getElementById('btn-save');
        if (btnSave) btnSave.addEventListener('click', () => this.saveWorkflow());

        const btnLoad = document.getElementById('btn-load');
        if (btnLoad) btnLoad.addEventListener('click', () => this.loadWorkflow());

        const btnClear = document.getElementById('btn-clear');
        if (btnClear) btnClear.addEventListener('click', () => this.clear());
    }

    async fetchMetaData() {
        try {
            const response = await fetch('api.php?action=get_meta_data');
            const data = await response.json();
            if (data.positions) {
                // Update NodeRegistry directly for OfficerReview
                if (NodeRegistry['OfficerReview']) {
                    const reviewWidget = NodeRegistry['OfficerReview'].widgets.find(w => w.name === 'Review');
                    if (reviewWidget) {
                        // We want names for the dropdown
                        reviewWidget.options = data.positions.map(p => p.name);
                    }
                }
            }
        } catch (error) {
            console.error('Failed to fetch metadata:', error);
            this.showNotification('Failed to load workflow data', 'error');
        }
    }

    updateTransform() {
        const transform = `translate(${this.panX}px, ${this.panY}px) scale(${this.scale})`;
        this.nodesContainer.style.transform = transform;
        this.connectionsLayer.style.transform = transform;
        if (this.tempConnectionsLayer) {
            this.tempConnectionsLayer.style.transform = transform;
        }

        // Grid parallax
        document.getElementById('grid').style.backgroundPosition = `${this.panX}px ${this.panY}px`;
        document.getElementById('grid').style.backgroundSize = `${20 * this.scale}px ${20 * this.scale}px`;
    }

    addNode(type, x, y, id = null, widgets = null) {
        if (!NodeRegistry[type]) return;

        // Singleton enforce for StartFlow
        if (type === 'StartFlow') {
            const existingStart = this.nodes.find(n => n.type === 'StartFlow');
            if (existingStart && !id) { // !id checks if it's a new node creation, not loading
                this.showNotification("Only one 'Start Flow' node is allowed.", 'warning');
                return;
            }
        }

        const node = new Node(this, x, y, type, id);
        if (widgets) {
            // Restore widget values
            Object.keys(widgets).forEach(name => {
                const widget = node.widgets.find(w => w.def.name === name);
                if (widget && widget.element) {
                    // Handle file upload widget value restoration specifically if needed
                    if (widget.def.type !== 'file_upload') {
                        widget.element.value = widgets[name];
                    } else {
                        // Create a simple text display for the filename if available
                        if (widgets[name]) {
                            // This part is tricky as invalid file input values cannot be set programmatically
                            // We might need to adjust how file widgets are verified or displayed on load
                            // For now, let's skip setting .value on file inputs directly
                            const display = widget.element.parentElement.querySelector('.file-display');
                            if (display) display.innerText = widgets[name];
                            widget.element.dataset.filename = widgets[name];
                        }
                    }
                }
            });
        }
        this.nodes.push(node);
        this.nodesContainer.appendChild(node.element);
        return node;
    }

    deleteNode(nodeId) {
        const nodeIndex = this.nodes.findIndex(n => n.id === nodeId);
        if (nodeIndex === -1) return;

        const node = this.nodes[nodeIndex];

        // Remove connections associated with this node
        this.connections = this.connections.filter(c => {
            const isConnected = c.output.nodeId === nodeId || c.input.nodeId === nodeId;
            if (isConnected && this.selectedConnection === c) {
                this.selectedConnection = null;
            }
            return !isConnected;
        });

        // Remove DOM element
        if (node.element) {
            node.element.remove();
        }

        // Remove from nodes array
        this.nodes.splice(nodeIndex, 1);

        // Update UI
        this.updateConnections();
        this.showNotification('Node deleted', 'info');
    }

    startConnectionDrag(nodeId, socketName, type, element) {

        this.draggedConnection = { nodeId, socketName, type, element };

        // Create temp path
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('class', 'connection-line temp');
        path.setAttribute('id', 'temp-connection');
        path.style.pointerEvents = 'none';
        path.style.stroke = '#ff6666'; // Distinct color for verify
        path.style.strokeWidth = '4px'; // Thicker line

        if (this.tempConnectionsLayer) {
            this.tempConnectionsLayer.innerHTML = '';
            this.tempConnectionsLayer.appendChild(path);
        } else {
            this.connectionsLayer.appendChild(path);
        }
    }

    updateTempConnection(x, y) {
        if (!this.draggedConnection) return;

        const startSocket = this.draggedConnection.element;
        const startRect = startSocket.getBoundingClientRect();
        const appRect = this.canvas.getBoundingClientRect();

        // Calculate start point in transformed space
        const startX = (startRect.left + startRect.width / 2 - appRect.left - this.panX) / this.scale;
        const startY = (startRect.top + startRect.height / 2 - appRect.top - this.panY) / this.scale;

        const d = this.calculateBezierPath(startX, startY, x, y);
        const tempPath = document.getElementById('temp-connection');
        if (tempPath) tempPath.setAttribute('d', d);
    }

    createConnection(source, target) {
        // Standardize: Source is Output, Target is Input
        let outputSock = source.type === 'output' ? source : target;
        let inputSock = source.type === 'input' ? source : target;

        if (outputSock.type !== 'output' || inputSock.type !== 'input') {
            console.error('Invalid connection types');
            return;
        }

        // Check for duplicates
        const exists = this.connections.some(c =>
            c.output.nodeId === outputSock.nodeId &&
            c.output.socketName === outputSock.socketName &&
            c.input.nodeId === inputSock.nodeId &&
            c.input.socketName === inputSock.socketName
        );

        if (exists) {
            this.showNotification('Connection already exists', 'warning');
            return;
        }

        const connection = {
            output: outputSock,
            input: inputSock
        };
        this.connections.push(connection);
        this.updateConnections();
    }

    deleteConnection(conn) {
        const index = this.connections.indexOf(conn);
        if (index > -1) {
            this.connections.splice(index, 1);
            if (this.selectedConnection === conn) {
                this.selectedConnection = null;
            }
            this.updateConnections();
            this.showNotification('Connection deleted', 'info');
        }
    }

    updateConnections() {
        // Clear existing lines (inefficient but simple for now)
        this.connectionsLayer.innerHTML = '';

        this.connections.forEach(conn => {
            const outSocket = conn.output.element;
            const inSocket = conn.input.element;

            if (!outSocket || !inSocket) return;

            const outRect = outSocket.getBoundingClientRect();
            const inRect = inSocket.getBoundingClientRect();
            const appRect = this.canvas.getBoundingClientRect();

            // Calculate coordinates in transformed space
            const x1 = (outRect.left + outRect.width / 2 - appRect.left - this.panX) / this.scale;
            const y1 = (outRect.top + outRect.height / 2 - appRect.top - this.panY) / this.scale;
            const x2 = (inRect.left + inRect.width / 2 - appRect.left - this.panX) / this.scale;
            const y2 = (inRect.top + inRect.height / 2 - appRect.top - this.panY) / this.scale;

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('class', 'connection-line');
            if (conn === this.selectedConnection) {
                path.classList.add('selected');
            }
            path.setAttribute('d', this.calculateBezierPath(x1, y1, x2, y2));

            // Add click listener for selection
            path.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent canvas click
                this.selectedConnection = conn;
                this.updateConnections(); // Re-render to show selection
            });

            this.connectionsLayer.appendChild(path);
        });
    }

    calculateBezierPath(x1, y1, x2, y2) {
        const dist = Math.abs(x1 - x2) * 0.5;
        const cp1x = x1 + dist;
        const cp1y = y1;
        const cp2x = x2 - dist;
        const cp2y = y2;
        return `M ${x1} ${y1} C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${x2} ${y2}`;
    }

    // Serialization
    serialize() {
        const data = {
            nodes: this.nodes.map(n => n.serialize()),
            connections: this.connections.map(c => ({
                output_node_id: c.output.nodeId,
                output_name: c.output.socketName,
                input_node_id: c.input.nodeId,
                input_name: c.input.socketName
            }))
        };
        return JSON.stringify(data, null, 2);
    }

    saveWorkflow() {
        const modal = document.getElementById('save-modal-overlay');
        const input = document.getElementById('save-filename');
        const descInput = document.getElementById('save-description');
        const confirmBtn = document.getElementById('btn-confirm-save');
        const cancelBtn = document.getElementById('btn-cancel-save');

        if (!modal || !input || !descInput || !confirmBtn || !cancelBtn) return;

        modal.classList.remove('hidden');

        const close = () => {
            modal.classList.add('hidden');
            // Clean up event listeners to avoid duplicates
            confirmBtn.onclick = null;
            cancelBtn.onclick = null;
        };

        // Pre-fill values
        if (this.currentMeta.name) input.value = this.currentMeta.name;
        if (this.currentMeta.description) descInput.value = this.currentMeta.description;

        confirmBtn.onclick = async () => {
            const name = input.value.trim();
            const description = descInput.value.trim();

            if (!name) {
                this.showNotification('Please enter a filename', 'warning');
                return;
            }

            const json = this.serialize();
            try {
                // Determine URL parameters
                const params = new URLSearchParams({
                    action: 'save',
                    name: name,
                    description: description
                });

                const response = await fetch('api.php?' + params.toString(), {
                    method: 'POST',
                    body: json
                });
                const result = await response.json();
                if (result.success) {
                    const savedName = result.filename || result.message || name;
                    this.showNotification('Saved: ' + savedName, 'success');
                    // Update meta
                    this.currentMeta.name = savedName;
                    this.currentMeta.description = description;

                    // For save, we might not have full meta (e.g. date/owner from DB), but we can simulate or fetch.
                    // For now, let's use what we have.
                    this.updateWorkflowInfo({
                        name: savedName,
                        description: description,
                        creator_name: 'System', // Or ideally fetch from response if API returned it
                        updated_at: new Date().toISOString()
                    });
                    close();
                } else {
                    this.showNotification('Error saving: ' + result.error, 'error');
                }
            } catch (e) {
                console.error(e);
                this.showNotification('Failed to save workflow.', 'error');
            }
        };

        cancelBtn.onclick = close;
    }

    async runWorkflow() {
        const json = this.serialize();
        const name = this.currentMeta.name || 'Untitled Execution';

        this.showNotification('Starting workflow execution...', 'info');

        try {
            const response = await fetch('api.php?action=run', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    workflow_json: json,
                    name: name
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showNotification('Workflow Executed! Instance ID: ' + result.instance_id, 'success');
                console.log('Execution Result:', result);
            } else {
                this.showNotification('Execution Failed: ' + result.error, 'error');
            }
        } catch (e) {
            console.error(e);
            this.showNotification('Failed to execute workflow.', 'error');
        }
    }

    async loadWorkflow() {
        const modal = document.getElementById('load-modal-overlay');
        const listContainer = document.getElementById('load-file-list');
        const confirmBtn = document.getElementById('btn-confirm-load');
        const cancelBtn = document.getElementById('btn-cancel-load');
        const hiddenInput = document.getElementById('selected-load-file');

        if (!modal || !listContainer || !confirmBtn || !cancelBtn) return;

        modal.classList.remove('hidden');
        listContainer.innerHTML = 'Loading...';
        confirmBtn.disabled = true;
        hiddenInput.value = '';

        const close = () => {
            modal.classList.add('hidden');
            confirmBtn.onclick = null;
            cancelBtn.onclick = null;
        };

        cancelBtn.onclick = close;

        try {
            const listRes = await fetch('api.php?action=list');
            const listData = await listRes.json();

            if (!listData.files || listData.files.length === 0) {
                listContainer.innerHTML = '<div style="padding:10px;color:#888;">No saved workflows found.</div>';
            } else {
                listContainer.innerHTML = '';

                // Create Structure: [ Dropdown ]
                //                   [ Details Panel ]

                // 1. Dropdown Container
                const selectContainer = document.createElement('div');
                selectContainer.className = 'workflow-select-container';

                const select = document.createElement('select');
                select.id = 'workflow-select';

                // Placeholder option
                const defaultOption = document.createElement('option');
                defaultOption.text = '-- Select a Workflow --';
                defaultOption.value = '';
                select.appendChild(defaultOption);

                listData.files.forEach(file => {
                    const option = document.createElement('option');
                    option.value = file.name;
                    option.text = file.name;
                    select.appendChild(option);
                });

                selectContainer.appendChild(select);
                listContainer.appendChild(selectContainer);

                // 2. Details Panel (Enhanced)
                const detailsPanel = document.createElement('div');
                detailsPanel.className = 'workflow-details-panel';
                detailsPanel.innerHTML = `
                    <div id="details-empty" class="details-empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <div>Select a workflow to view details</div>
                    </div>
                    <div id="details-content" class="hidden-content">
                        <div class="details-grid">
                            <div class="detail-group">
                                <span class="detail-label">Name</span>
                                <span class="detail-value" id="detail-name">-</span>
                            </div>
                            <div class="detail-group">
                                <span class="detail-label">Owner</span>
                                <span class="detail-value" id="detail-owner">-</span>
                            </div>
                            <div class="detail-group">
                                <span class="detail-label">Last Updated</span>
                                <span class="detail-value" id="detail-date">-</span>
                            </div>
                        </div>
                        <div class="detail-desc-group">
                            <span class="detail-label">Description</span>
                            <div class="detail-desc-box" id="detail-desc">-</div>
                        </div>
                    </div>
                `;
                listContainer.appendChild(detailsPanel);

                // Event Listener
                select.addEventListener('change', () => {
                    const selectedName = select.value;
                    const file = listData.files.find(f => f.name === selectedName);
                    const emptyState = document.getElementById('details-empty');
                    const contentState = document.getElementById('details-content');

                    if (file) {
                        const updated = new Date(file.updated_at).toLocaleDateString() + ' ' + new Date(file.updated_at).toLocaleTimeString();
                        const creator = file.creator_name || 'System';

                        document.getElementById('detail-name').innerText = file.name;
                        document.getElementById('detail-owner').innerText = creator;
                        document.getElementById('detail-date').innerText = updated;
                        document.getElementById('detail-desc').innerText = file.description || 'No description available.';

                        detailsPanel.classList.add('active');
                        emptyState.style.display = 'none';
                        contentState.classList.remove('hidden-content');
                        contentState.classList.add('visible-content');

                        hiddenInput.value = file.name;
                        confirmBtn.disabled = false;
                    } else {
                        detailsPanel.classList.remove('active');
                        emptyState.style.display = 'block';
                        contentState.classList.remove('visible-content');
                        contentState.classList.add('hidden-content');

                        hiddenInput.value = '';
                        confirmBtn.disabled = true;
                    }
                });
            }

            confirmBtn.onclick = async () => {
                const file = hiddenInput.value;
                if (!file) return;

                try {
                    const res = await fetch('api.php?action=load&file=' + encodeURIComponent(file));
                    if (!res.ok) {
                        this.showNotification("Failed to load file.", 'error');
                        return;
                    }

                    const json = await res.json();

                    if (json.meta && json.content) {
                        // New format
                        this.deserialize(json.content);
                        this.currentMeta = {
                            name: json.meta.name,
                            description: json.meta.description
                        };
                        this.updateWorkflowInfo(json.meta);
                    } else {
                        // Old format fallback
                        this.deserialize(json);
                        this.currentMeta = { name: file, description: 'Legacy Format' };
                        this.updateWorkflowInfo(this.currentMeta);
                    }

                    close();
                    this.showNotification('Workflow loaded: ' + file, 'success');
                } catch (e) {
                    console.error(e);
                    this.showNotification('Error loading workflow.', 'error');
                }
            };

        } catch (e) {
            console.error(e);
            listContainer.innerText = 'Error loading file list.';
        }
    }

    deserialize(data) {
        this.clear();

        // Create nodes
        data.nodes.forEach(n => {
            this.addNode(n.type, n.x, n.y, n.id, n.widgets_values);
        });

        // Draw connections after slight delay to ensure DOM render
        setTimeout(() => {
            data.connections.forEach(c => {
                const outNode = this.nodes.find(n => n.id === c.output_node_id);
                const inNode = this.nodes.find(n => n.id === c.input_node_id);

                if (outNode && inNode) {
                    const outSocket = outNode.getOutputSocket(c.output_name);
                    const inSocket = inNode.getInputSocket(c.input_name);

                    if (outSocket && inSocket) {
                        this.connections.push({ output: outSocket, input: inSocket });
                    }
                }
            });
            this.updateConnections();
        }, 50);
    }

    clear() {
        this.nodesContainer.innerHTML = '';
        this.connectionsLayer.innerHTML = '';
        this.nodes = [];
        this.connections = [];
        this.currentMeta = { name: '', description: '' };
        this.updateWorkflowInfo(null);
    }

    updateWorkflowInfo(meta) {
        if (meta && meta.name) {
            // Show Panel, Hide Empty
            if (this.menuInfoPanel) this.menuInfoPanel.style.display = 'flex';
            if (this.menuInfoEmpty) this.menuInfoEmpty.style.display = 'none';

            // Populate Data
            if (this.menuName) this.menuName.innerText = meta.name;

            const owner = meta.creator_name || 'System';
            const dateStr = meta.updated_at ? new Date(meta.updated_at).toLocaleDateString() : 'Just now';

            if (this.menuOwner) this.menuOwner.innerText = owner;
            if (this.menuDate) this.menuDate.innerText = dateStr;

            if (this.menuDesc) {
                this.menuDesc.innerText = meta.description || 'No description.';
                this.menuDesc.style.display = meta.description ? 'block' : 'none';
            }
        } else {
            // Hide Panel, Show Empty
            if (this.menuInfoPanel) this.menuInfoPanel.style.display = 'none';
            if (this.menuInfoEmpty) this.menuInfoEmpty.style.display = 'block';
        }
    }
}

class ContextMenu {
    constructor(app) {
        this.app = app;
        this.element = document.createElement('div');
        this.element.id = 'context-menu';
        document.body.appendChild(this.element);
        this.activeX = 0;
        this.activeY = 0;
    }

    show(x, y) {
        this.element.innerHTML = '';
        this.element.style.display = 'block';
        this.element.style.left = `${x}px`;
        this.element.style.top = `${y}px`;
        this.activeX = x;
        this.activeY = y;

        // Populate menu
        // Menu Structure
        const menuItems = [
            { label: 'Start Flow', type: 'StartFlow' },
            { label: 'Officer Review', type: 'OfficerReview' },
            { label: 'Manager Approval', type: 'ManagerApproval' },
            { label: 'Condition', type: 'Condition' },
            {
                label: 'System Action',
                children: [
                    { label: 'Send Email', type: 'SystemAction', widgets: { action: 'Send Email' } },
                    { label: 'Update Database', type: 'SystemAction', widgets: { action: 'Update Database' } }
                ]
            },
            { label: 'End Flow', type: 'EndFlow' }
        ];

        const createMenuItem = (item, parent) => {
            const div = document.createElement('div');
            div.className = 'context-menu-item';
            div.innerText = item.label;

            if (item.children) {
                div.classList.add('has-submenu');
                const submenu = document.createElement('div');
                submenu.className = 'context-submenu';
                item.children.forEach(child => createMenuItem(child, submenu));
                div.appendChild(submenu);
            } else {
                div.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const rect = this.app.canvas.getBoundingClientRect();
                    const nodeX = (this.activeX - rect.left - this.app.panX) / this.app.scale;
                    const nodeY = (this.activeY - rect.top - this.app.panY) / this.app.scale;

                    this.app.addNode(item.type, nodeX, nodeY, null, item.widgets);
                    this.hide();
                });
            }
            parent.appendChild(div);
        };

        menuItems.forEach(item => createMenuItem(item, this.element));
    }

    hide() {
        this.element.style.display = 'none';
    }
}

class Node {
    constructor(app, x, y, type, id = null) {
        this.app = app;
        this.id = id || ('node-' + Date.now() + '-' + Math.floor(Math.random() * 1000));
        this.x = x;
        this.y = y;
        this.type = type;
        this.data = JSON.parse(JSON.stringify(NodeRegistry[type])); // Deep copy definition

        this.inputs = [];
        this.outputs = [];
        this.widgets = [];

        this.element = this.createElement();
        this.updateElementPosition();
    }

    createElement() {
        const el = document.createElement('div');
        el.classList.add('node');
        if (this.data.category) {
            el.classList.add(`category-${this.data.category}`);
        }
        el.id = this.id;
        el.style.position = 'absolute';

        const header = document.createElement('div');
        header.classList.add('node-header');

        const titleSpan = document.createElement('span');
        titleSpan.innerText = this.data.title;
        header.appendChild(titleSpan);

        // Delete Button (Bin Icon)
        const deleteBtn = document.createElement('span');
        deleteBtn.innerHTML = '🗑️'; // Bin icon
        deleteBtn.className = 'node-delete-btn';
        deleteBtn.title = 'Delete Node';
        deleteBtn.addEventListener('mousedown', (e) => e.stopPropagation()); // Prevent drag
        deleteBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.app.showConfirmModal('Are you sure you want to delete this node?', () => {
                this.app.deleteNode(this.id);
            });
        });
        header.appendChild(deleteBtn);

        header.addEventListener('mousedown', (e) => {
            e.stopPropagation(); // Prevent canvas panning
            this.app.draggedNode = this;
            this.app.dragStartX = e.clientX;
            this.app.dragStartY = e.clientY;
            this.initialX = this.x;
            this.initialY = this.y;

            // Bring to front
            this.app.nodesContainer.appendChild(el);
        });

        this.body = document.createElement('div');
        this.body.classList.add('node-body');

        // Container for inputs/outputs
        this.inputsContainer = document.createElement('div');
        this.outputsContainer = document.createElement('div');
        this.widgetsContainer = document.createElement('div');

        this.body.appendChild(this.inputsContainer);
        this.body.appendChild(this.widgetsContainer);
        this.body.appendChild(this.outputsContainer);

        el.appendChild(header);
        el.appendChild(this.body);

        // Initialize Inputs/Outputs/Widgets from definition
        if (this.data.inputs) {
            this.data.inputs.forEach(input => this.addInput(input.name, input.type));
        }
        if (this.data.outputs) {
            this.data.outputs.forEach(output => this.addOutput(output.name, output.type));
        }
        if (this.data.widgets) {
            this.data.widgets.forEach(widget => this.addWidget(widget));
        }

        return el;
    }

    addInput(name, type) {
        const div = document.createElement('div');
        div.classList.add('connection-point');
        div.innerText = name;
        div.style.textAlign = 'left';

        const socket = document.createElement('div');
        socket.classList.add('socket', 'input');
        socket.dataset.type = 'input';
        socket.dataset.nodeId = this.id;
        socket.dataset.name = name;
        socket.title = type;

        socket.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            this.app.startConnectionDrag(this.id, name, 'input', socket);
        });

        div.prepend(socket);
        this.inputsContainer.appendChild(div);
        this.inputs.push({ nodeId: this.id, socketName: name, name, type, element: socket });
    }

    addOutput(name, type) {
        const div = document.createElement('div');
        div.classList.add('connection-point');
        div.innerText = name;
        div.style.textAlign = 'right';

        const socket = document.createElement('div');
        socket.classList.add('socket', 'output');
        socket.dataset.type = 'output';
        socket.dataset.nodeId = this.id;
        socket.dataset.name = name;
        socket.title = type;

        socket.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            this.app.startConnectionDrag(this.id, name, 'output', socket);
        });

        div.append(socket);
        this.outputsContainer.appendChild(div);
        this.outputs.push({ nodeId: this.id, socketName: name, name, type, element: socket });
    }

    addWidget(widgetDef) {
        const div = document.createElement('div');
        div.className = 'widget-container';

        const label = document.createElement('div');
        label.innerText = widgetDef.name;
        label.style.fontSize = '0.8em';
        label.style.color = '#aaa';
        div.appendChild(label);

        let input;

        if (widgetDef.type === 'file_upload') {
            input = document.createElement('div');
            input.className = 'file-drop-zone';
            input.dataset.filename = widgetDef.value || '';

            // Style properties directly for simplicity
            input.style.border = '2px dashed #666';
            input.style.padding = '10px';
            input.style.margin = '5px 0';
            input.style.textAlign = 'center';
            input.style.cursor = 'pointer';
            input.style.color = '#ccc';

            const displayText = document.createElement('span');
            displayText.className = 'file-display';
            displayText.innerText = widgetDef.value ? widgetDef.value : 'Drag & Drop File Here';
            displayText.style.pointerEvents = 'none'; // click goes to container
            input.appendChild(displayText);

            // Drag and Drop Events
            input.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                input.style.borderColor = '#0f0';
                input.style.backgroundColor = 'rgba(0, 255, 0, 0.1)';
            });

            input.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();
                input.style.borderColor = '#666';
                input.style.backgroundColor = 'transparent';
            });

            input.addEventListener('drop', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                input.style.borderColor = '#666';
                input.style.backgroundColor = 'transparent';

                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    const file = e.dataTransfer.files[0];
                    displayText.innerText = 'Uploading: ' + file.name;

                    // Upload File
                    const formData = new FormData();
                    formData.append('file', file);

                    try {
                        const res = await fetch('api.php?action=upload', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await res.json();

                        if (result.success) {
                            displayText.innerText = result.filename;
                            input.dataset.filename = result.filename; // Store filename in dataset
                            // Also need to simulate an input value for serialization
                            input.value = result.filename;
                            this.app.showNotification('File uploaded successfully: ' + result.filename, 'success');
                        } else {
                            displayText.innerText = 'Error: ' + result.error;
                            this.app.showNotification('Upload Failed: ' + result.error, 'error');
                        }
                    } catch (err) {
                        console.error(err);
                        displayText.innerText = 'Upload Error';
                        this.app.showNotification('Upload Failed', 'error');
                    }
                }
            });

            // Also allow click to select (Optional addition)
            // ...

        } else if (widgetDef.type === 'text') {
            input = document.createElement('input');
            input.type = 'text';
            input.value = widgetDef.value;
        } else if (widgetDef.type === 'number') {
            input = document.createElement('input');
            input.type = 'number';
            input.value = widgetDef.value;
        } else if (widgetDef.type === 'select') {
            input = document.createElement('select');
            widgetDef.options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt;
                option.innerText = opt;
                input.appendChild(option);
            });
        }

        if (input) {
            // Important for file upload div too just in case
            input.addEventListener('mousedown', (e) => e.stopPropagation());
            input.addEventListener('wheel', (e) => e.stopPropagation());
            div.appendChild(input);
        }

        this.widgetsContainer.appendChild(div);
        this.widgets.push({ def: widgetDef, element: input });
    }

    getInputSocket(name) {
        return this.inputs.find(i => i.name === name);
    }

    getOutputSocket(name) {
        return this.outputs.find(o => o.name === name);
    }

    updateElementPosition() {
        this.element.style.transform = `translate(${this.x}px, ${this.y}px)`;
    }

    serialize() {
        const widgetsValues = {};
        this.widgets.forEach(w => {
            if (w.element) {
                if (w.def.type === 'file_upload') {
                    widgetsValues[w.def.name] = w.element.dataset.filename || '';
                } else {
                    widgetsValues[w.def.name] = w.element.value;
                }
            }
        });

        return {
            id: this.id,
            type: this.type,
            x: this.x,
            y: this.y,
            widgets_values: widgetsValues
        };
    }
}

// Initialize App
window.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
});
