# Workflow Builder & Document Management System

A web-based visual workflow editor and document management system inspired by ComfyUI. Built with vanilla JavaScript, CSS, and PHP. This project allows users to create visual workflows, track documents, manage delegations, and analyze system performance.

## Features

### Visual Node Editor (`flowBilder.php`)
- **Drag and Drop:** Intuitive canvas for arranging nodes.
- **Connections:** Link nodes via Input/Output sockets to define flow logic.
- **Dynamic Logic:** True/False conditions are dynamically evaluated against real document variables (e.g., amount, department).
- **Navigation:** Zoom and Pan support for large workflows.
- **Workflow Management:** Save/Load workflows with metadata (Owner, Date, Description).
- **Execution Engine:** Robust PHP backend with Infinite Loop protection for safely running workflows.

### Document Management
- **New Document Flow (`docFlow.php`)**:
  - Modern glassmorphism UI for initiating document requests.
  - Features real-time **Timeline Preview** of the selected workflow.
  - Supports multi-file attachments via drag-and-drop.
  
- **Document Tracker (`tracker.php`)**:
  - **Dashboard**: "My Requests" dashboard with status cards (Total, Pending, Completed, Rejected).
  - **Status Badge**: Visual indicators for document status.
  - **Delegation Management**: Temporarily assign your document approval authority to a colleague directly from the UI.
  - **History**: Track every document submitted by the user.

- **Inbox (`inbox.php`)**:
  - **Pending Approvals**: View documents awaiting your action based on role/position.
  - **Department-Aware Routing**: Managers will only see documents routed from their own department.
  - **Virtual Profiles**: Seamlessly handles documents routed to you via active Delegation rules without manual login swapping.
  - **Action Controls**: Approve or Reject documents with remarks to progress workflow.

- **Workflow Review (`review.php`)**:
  - Read-only "Stepper" view to visualize saved workflows purely as a linear process for easier understanding.

### Security & Transparency
- **Action History (Audit Log)**: Every approval, rejection, or submission is indelibly recorded with timestamps and actor identities.

### Analytics (`statistics.php`)
- **Dashboard**:
  - **Volume Metrics**: Total Documents & Total Budget Requested.
  - **Department Analysis**: Pie chart showing request distribution by department.
  - **Status Breakdown**: Doughnut chart showing the success rate of workflows.

### User System
- **Registration**: Extended fields (Employee ID, Position, Department).
- **Authentication**: Secure login with role-based attributes.

## API Documentation (Router & Controllers)

The backend has been refactored into an MVC-style router pattern. `api.php` acts as the central router, delegating requests to specific controllers based on the `action` query parameter.

### Controllers
- **`AuthController.php`**: Handles `login`, `register`, `logout`, `check_auth`.
- **`WorkflowController.php`**: Handles `list`, `load`, `save`, `run`.
- **`DocumentController.php`**: Handles `start_document`, `track_documents`, `upload`, `get_inbox`, `process_document`, `get_document_history`.
- **`UserController.php`**: Handles `get_users`, `get_meta_data`, `save_delegation`, `get_my_delegations`, `revoke_delegation`.
- **`AnalyticsController.php`**: Handles `get_tracker_stats`, `get_statistics`.

*(Note: The underlying query parameters remain the same for backward compatibility with the frontend interfaces.)*

## Project Structure

- **`controllers/`**: Domain-specific logic (`AuthController.php`, etc.).
- **`flowBilder.php`**: Main visual editor.
- **`docFlow.php`**: Document creation interface.
- **`tracker.php`**: User document & delegation dashboard.
- **`inbox.php`**: User pending approvals inbox.
- **`statistics.php`**: Analytics dashboard.
- **`review.php`**: Workflow stepper view.
- **`app.js`**: Core logic for the editor.
- **`api.php`**: Central API router.
- **`WorkflowEngine.php`**: Backend execution logic.
- **`database/`**: SQLite database storage.

## Performance Optimizations

1. **Singleton Database Connection**: `getDB()` utilizes the Singleton pattern to reuse the PDO instance per request, drastically reducing connection overhead.
2. **Lazy Loading Controllers**: The `spl_autoload_register` function is used so PHP only includes controller files into memory when they are actually instantiated by the router.
3. **OPcache Array Flow Cache**: Workflow JSON definitions are pre-compiled into native `.php` array files (`storage/array_cache/`) so they bypass file reading constraints and load directly into server memory.
4. **Optimized Inbox Queries**: Document retrieval eliminates the N+1 problem by leveraging `UNION ALL` to build dynamic delegation profiles in one sweep.
5. **Debounced Graph Rendering**: The Node Editor (`app.js`) implements `requestAnimationFrame` to limit calculation intervals, preserving FPS smoothing when dragging nodes in large flowcharts.

## Data Dictionary

### `users`
| Field | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `id` | TEXT | PK | Employee ID (Manually entered). |
| `username` | TEXT | UNIQUE | System login username. |
| `email` | TEXT | | Contact email. |
| `password_hash` | TEXT | | PHP `password_hash` output. |
| `position_id` | INT | FK | References `positions(id)`. |
| `dept_id` | INT | FK | References `departments(id)`. |

### `workflow_definitions`
| Field | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `id` | INT | PK, AI | Unique ID. |
| `name` | TEXT | | Workflow Display Name. |
| `description` | TEXT | | Optional description. |
| `workflow_file` | TEXT | | Filename of the JSON stored in `storage/`. |
| `creator_name` | TEXT | | Username of creator. |
| `updated_at` | DATETIME | | Last modification timestamp. |

### `workflow_instances`
| Field | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `id` | INT | PK, AI | Instance ID. |
| `workflow_name` | TEXT | | Name of flow being executed. |
| `status` | TEXT | | Current status (e.g., PENDING, RUNNING, COMPLETED). |
| `current_node_id` | TEXT | | ID of the node currently active. |
| `data` | TEXT | | JSON Snapshot of the workflow at execution time. |

### `documents`
| Field | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `doc_id` | TEXT | PK | Format: DOC-xxxx. |
| `doc_number` | TEXT | UNIQUE | Internal Running Number Format: YYYYMMDDxxxx. |
| `doc_title` | TEXT | | Document Title. |
| `doc_amount` | DECIMAL | | Budget/Amount. |
| `dept_id` | INT | FK | Origin Department. |
| `user_id` | INT | FK | User who created the doc. |
| `workflow_id` | INT | FK | ID of the Workflow Definition used. |
| `current_node_id` | TEXT | | ID of the current active node. |
| `status` | TEXT | | e.g., START, PENDING. |
| `created_at` | DATETIME | | Creation Timestamp. |

### `workflow_logs`
| Field | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `id` | INT | PK, AI | Log ID. |
| `document_id` | TEXT | FK | Linked Document ID. |
| `actor_id` | TEXT | FK | User who performed the action. |
| `node_id` | TEXT | | Node where action occurred. |
| `action` | TEXT | | Action taken (e.g., APPROVED). |
| `comment` | TEXT | | Optional remarks. |
| `created_at`| DATETIME | | Timestamp of the action. |

### `delegations`
| Field | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `id` | INT | PK, AI | Delegation Process ID. |
| `delegator_id`| TEXT | FK | User giving the authority. |
| `delegatee_id`| TEXT | FK | User receiving the authority. |
| `start_date` | DATETIME | | Start window of delegation. |
| `end_date` | DATETIME | | End window of delegation. |
| `status` | TEXT | | e.g. ACTIVE or REVOKED. |
| `created_at`| DATETIME | | Creation Timestamp. |

### `document_files`
| Field | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `id` | INT | PK, AI | File ID. |
| `document_id` | INT | FK | Linked Document. |
| `filename` | TEXT | | Original Filename. |
| `file_path` | TEXT | | Relative path (e.g., `202602110001/file.pdf`). |
| `uploaded_at` | DATETIME | | Upload Timestamp. |

## Workflow JSON Structure

The workflow is saved as a JSON object containing nodes and connections.

```json
{
  "nodes": [
    {
      "id": "node_unique_id",
      "type": "NodeType (e.g., StartFlow)",
      "x": 100, // X Coord on Canvas
      "y": 200, // Y Coord on Canvas
      "widgets_values": {
        "widget_name": "value"
      }
    }
  ],
  "connections": [
    {
      "output_node_id": "source_id",
      "output_name": "socket_name (e.g., start)",
      "input_node_id": "target_id",
      "input_name": "socket_name (e.g., EXEC)"
    }
  ]
}
```
