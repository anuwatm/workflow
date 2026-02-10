# Workflow Builder (ComfyUI Clone)

A web-based visual workflow editor inspired by ComfyUI, built with vanilla JavaScript, CSS, and PHP. This project allows users to create, save, and load node-based workflows with a modern, dark-themed interface.

## Features

- **Visual Node Editor:** 
  - Drag and drop nodes.
  - Connect nodes via sockets (Input/Output).
  - Zoom and Pan canvas navigation.
  - Right-click context menu to add nodes.
  
- **Workflow Management:**
  - **Save:** Save your current workflow with a name and description.
  - **Load:** Browse saved workflows via a dropdown menu with detailed metadata (Owner, Date, Description).
  - **Clear:** Reset the canvas to start fresh.
  
- **Modern UI:**
  - **Floating Menu:** Access key actions (Start, Save, Load, Clear) and view current workflow details in a collapsible right-hand panel.
  - **Dark Mode:** Sleek, dark-themed interface for comfortable use.
  - **Responsive Details:** Workflow information is displayed elegantly within the main menu.

## Project Structure

- **`flowBilder.php`**: The main application entry point (HTML structure).
- **`app.js`**: Core logic for the node editor, interactions, and API communication.
- **`style.css`**: All styling for the application, including nodes, connections, and UI panels.
- **`api.php`**: Backend API for handling file operations (Save/Load) and database interactions.
- **`database/`**: Contains the SQLite database (`database.sqlite`) and schema.
- **`storage/`**: Directory where actual workflow JSON files are stored.

## Setup & specific configuration

1. **Environment:** Requires a PHP server (e.g., LocalDevine, XAMPP, or built-in PHP server) with SQLite enabled.
2. **Database:** Ensure `database/database.sqlite` exists and is writable. `api.php` handles basic migration checks.
3. **Storage:** Ensure the `storage/` directory is writable by the web server.

## Usage

1. Open `flowBilder.php` in your browser.
2. **Add Nodes:** Right-click on the grid background to open the context menu and select a node type.
3. **Connect:** Drag from one node's output socket to another node's input socket.
4. **Save:** Click "Save Workflow", enter a name and description.
5. **Load:** Click "Load Workflow", select a workflow from the dropdown to preview its details, then click "Load".

## Credits

Created as a custom workflow solution with a focus on ease of use and visual appeal.
