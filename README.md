# Workflow Builder

A web-based visual workflow editor inspired by ComfyUI, built with vanilla JavaScript, CSS, and PHP. This project allows users to create, save, load, and execute node-based workflows with a modern, dark-themed interface.

## Features

### Visual Node Editor
- **Drag and Drop:** Intuitive canvas for arranging nodes.
- **Connections:** Link nodes via Input/Output sockets to define flow logic.
- **Navigation:** Zoom and Pan support for large workflows.
- **Context Menu:** Right-click to add new nodes.

### Workflow Management
- **Save/Load:** Persist workflows to the server (JSON storage) with metadata (Owner, Date, Description).
- **Listing:** Browse saved workflows with detailed previews in the main menu.
- **Review:** Visualize workflows in a simplified linear "Stepper" view for easier review of the process flow.
- **Clear:** Reset the canvas instantly.

### Document Creation & Tracking (New!)
- **Document Flow:** Create new documents linked to workflows with a modern, glassmorphism UI.
- **Visual Preview:** See the approval timeline before submitting.
- **File Attachments:** Drag & drop file uploads stored in dedicated document folders.
- **Tracking:** Monitor document status (Start, Pending, Approved) via a unique Document ID.

### User System (New!)
- **Registration:**
  - Extended fields: Employee ID (`empID`), Username, Email, Password.
  - **Role Management:** Select Position and Department during registration (dynamically populated from database).
- **Authentication:** Secure login system with password hashing.

### Workflow Execution (Backend)
- **Execution Engine:** PHP-based engine to traverse and execute workflow graphs.
- **Instance Tracking:** Database records for every workflow run (Status, Current Node, Logs).
- **(In Progress):** Logic for pausing execution at Human Tasks (Review/Approval).

## Nodes

| Node Type | Category | Inputs | Outputs | Widgets | Description |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Start Flow** | Start-End | - | `start` | - | Entry point of the workflow. Only one allowed per flow. |
| **Officer Review** | Human | `INPUT` | `PASSED`<br>`REJECTED` | `Review` (Position)<br>`remark` (Text) | Assigns a review task to a specific position. |
| **Manager Approval** | Human | `INPUT` | `APPROVED`<br>`DENIED` | `level` (Role)<br>`threshold` (Number) | Assigns an approval task based on management level and value threshold. |
| **Condition** | Logic | `EXEC`<br>`DATA` | `TRUE`<br>`FALSE` | `field` (Select)<br>`operator` (Select)<br>`value` (Text) | Logic branch based on data comparison (e.g., Amount > 1000). |
| **System Action** | System | `EXEC`<br>`DATA` | `DONE` | `action` (Select)<br>`recipient` (Text) | Automated system tasks like sending emails or updating databases. |
| **End Flow** | Start-End | `INPUT` | - | `status` (Select) | Terminal point. Sets the final status of the workflow instance. |

## API Documentation

The backend `api.php` handles all requests via query parameter `action`.

### Authentication
- **`POST ?action=login`**: Authenticate user. Body: `{ "username": "...", "password": "..." }`
- **`POST ?action=register`**: Create new user. Body: `{ "emp_id": "...", "username": "...", "password": "...", "email": "...", "position_id": "...", "dept_id": "..." }`
- **`POST ?action=logout`**: Destroy session.
- **`GET ?action=check_auth`**: Check session status. Returns `{ "authenticated": true/false, "username": "..." }`

### Workflow Operations
- **`GET ?action=list`**: Get all saved workflows with metadata.
- **`GET ?action=load&file=[name]`**: Get specific workflow JSON and metadata.
- **`POST ?action=save&name=[name]&description=[desc]`**: Save workflow. Body: JSON String.
- **`POST ?action=run`**: Execute workflow. Body: `{ "workflow_json": {...}, "name": "..." }`
- **`POST ?action=upload`**: Upload file for widgets.

### Document Operations
- **`POST ?action=start_document`**: Create a new document instance.
  - Params: `title`, `amount`, `dept_id`, `workflow_id`.
  - Files: `files[]` (Multipart).
  - Returns: `{ "success": true, "doc_id": "...", "doc_no": "..." }`
- **`GET ?action=get_user_details`**: Fetch current session user's details (Username, Position, Department).

### Metadata
- **`GET ?action=get_meta_data`**: Returns available `positions` and `departments` for registration.

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
| `id` | INT | PK, AI | Internal ID. |
| `doc_no` | TEXT | UNIQUE | Format: YYYYMMDDxxxx. |
| `title` | TEXT | | Document Title. |
| `amount` | DECIMAL | | Budget/Amount. |
| `dept_id` | INT | FK | Origin Department. |
| `requester_id` | INT | FK | User who created the doc. |
| `workflow_id` | INT | FK | ID of the Workflow Definition used. |
| `current_node` | TEXT | | ID of the current active node. |
| `status` | TEXT | | e.g., START, PENDING. |
| `created_at` | DATETIME | | Creation Timestamp. |

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

## Project Structure

- **`flowBilder.php`**: Main application interface.
- **`review.php`**: Read-only view for reviewing saved workflows.
- **`docFlow.php`**: Interface for initiating new document workflows.
- **`tracker.php`**: Page for tracking submitted document status.
- **`app.js`**: Core logic for the editor, node management, and API calls.
- **`api.php`**: Backend API for Auth, Workflow CRUD, and Execution.
- **`WorkflowEngine.php`**: Class responsible for executing workflow logic.
- **`database/`**: SQLite database storage.
- **`setup_extended_db.php`**: Script to initialize the extended database schema (Positions, Departments).
