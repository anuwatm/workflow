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
- **Clear:** Reset the canvas instantly.

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

| Node Type | Description | Inputs | Outputs |
| :--- | :--- | :--- | :--- |
| **Start Flow** | Entry point of the workflow. | - | `start` |
| **Review** | Human task for officer review. | `INPUT` | `PASSED`, `REJECTED` |
| **Approval** | Human task for manager approval. | `INPUT` | `APPROVED`, `DENIED` |
| **Condition** | Logic branch based on data values. | `EXEC`, `DATA` | `TRUE`, `FALSE` |
| **System Action** | Automated system tasks (Email, DB Update). | `EXEC`, `DATA` | `DONE` |
| **End Flow** | Terminal point of the workflow. | `INPUT` | - |

## Project Structure

- **`flowBilder.php`**: Main application interface.
- **`app.js`**: Core logic for the editor, node management, and API calls.
- **`api.php`**: Backend API for Auth, Workflow CRUD, and Execution.
- **`WorkflowEngine.php`**: Class responsible for executing workflow logic.
- **`database/`**: SQLite database storage.
- **`setup_extended_db.php`**: Script to initialize the extended database schema (Positions, Departments).

## Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/anuwatm/workflow.git
   cd workflow
   ```

2. **Database Initialization:**
   - Ensure PHP has `pdo_sqlite` enabled.
   - Run `setup_extended_db.php` (via browser or CLI) to create tables and seed data.

3. **Storage:**
   - Ensure `storage/` directory is writable.

## Usage

1. **Register:** Create an account with your Employee ID and Role.
2. **Design:** Use the editor to build a workflow. Start with **Start Flow**, add **Reviews/Approvals**, and end with **End Flow**.
3. **Save:** Save your design.
4. **Run:** Click "Run Workflow" to execute the current design (Basic execution implemented).

## Credits
Created as a custom workflow solution. 
> **Note:** This code was developed with the assistance of **Google Gemini**.
