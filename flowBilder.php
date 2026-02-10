<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ComfyUI Clone</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Embed SVG definitions if needed */
    </style>
</head>

<body>

    <div id="app">
        <!-- Layer 1: Grid Background -->
        <div class="grid-background" id="grid"></div>

        <!-- Layer 2: Connections (SVG) -->
        <svg id="connections-layer">
            <!-- Lines will be dynamically added here -->
        </svg>

        <!-- Layer 3: Nodes Container -->
        <div id="nodes-container">
            <!-- Nodes will be injected here via JS -->
        </div>

        <!-- Layer 4: Temp Connections (Dragging) -->
        <svg id="temp-connections-layer"></svg>

        <!-- Layer 5: UI Controls -->
        <div id="ui-layer">

            <div id="notification-area"></div>
            <div id="modal-overlay" class="hidden">
                <div class="modal">
                    <div class="modal-header">Confirm Action</div>
                    <div class="modal-body" id="modal-text">Are you sure?</div>
                    <div class="modal-footer">
                        <button id="modal-confirm-btn" class="btn-danger">Yes</button>
                        <button id="modal-cancel-btn">No</button>
                    </div>
                </div>
            </div>

            <!-- Save Modal -->
            <div id="save-modal-overlay" class="hidden">
                <div class="modal">
                    <div class="modal-header">Save Workflow</div>
                    <div class="modal-body">
                        <label for="save-filename" style="color:#ddd;">Filename:</label>
                        <input type="text" id="save-filename" value="my_workflow"
                            style="margin-top:5px;width:100%;margin-bottom:10px;">
                        <label for="save-description" style="color:#ddd;">Description:</label>
                        <textarea id="save-description"
                            style="margin-top:5px;width:100%;height:60px;background:#222;border:1px solid #444;color:#ddd;border-radius:3px;"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button id="btn-confirm-save" style="background:#28a745;color:white;">Save</button>
                        <button id="btn-cancel-save">Cancel</button>
                    </div>
                </div>
            </div>

            <!-- Load Modal -->
            <div id="load-modal-overlay" class="hidden">
                <div class="modal" style="width: 400px;">
                    <div class="modal-header">Load Workflow</div>
                    <div class="modal-body">
                        <div id="load-file-list" class="file-list">Loading...</div>
                        <input type="hidden" id="selected-load-file">
                    </div>
                    <div class="modal-footer">
                        <button id="btn-confirm-load" style="background:#007bff;color:white;" disabled>Load</button>
                        <button id="btn-cancel-load">Cancel</button>
                    </div>
                </div>
            </div>
            <div id="main-menu" class="floating-panel">
                <div class="menu-buttons">
                    <button id="btn-start-flow">Start Flow</button>
                    <button id="btn-save">Save Workflow</button>
                    <button id="btn-load">Load Workflow</button>
                    <button id="btn-clear">Clear</button>
                </div>
                <div id="workflow-info-panel" class="workflow-info-panel" style="display:none;">
                    <div id="menu-workflow-name" class="menu-workflow-name"></div>
                    <div class="menu-workflow-meta">
                        <span id="menu-workflow-owner"></span> • <span id="menu-workflow-date"></span>
                    </div>
                    <div id="menu-workflow-desc" class="menu-workflow-desc"></div>
                </div>
                <div id="workflow-info-empty" class="workflow-info-empty">No Workflow Loaded</div>
            </div>
        </div>
    </div>

    <script src="app.js"></script>
</body>

</html>