# ⚡ FlowCraft - Visual Automation Builder

FlowCraft is a full-stack, node-based workflow automation system inspired by tools like n8n and Zapier. It is designed with a strong focus on **Clean Architecture**, **Dynamic Schema Generation**, and **Multi-path Execution**.

---

## 🏗️ System Architecture

The system is fully decoupled into:

* **Backend**: Laravel (REST API)
* **Frontend**: React + React Flow

### 🔹 Backend Design

* Implements the **Strategy Pattern** and **Dependency Injection**
* Each node is an isolated class implementing `NodeInterface`
* New nodes can be added without modifying the execution engine

### 🔹 Frontend Design

* Uses a single `DynamicNode` component
* Dynamically renders UI based on JSON schema from backend:

  * Text inputs
  * Select dropdowns
  * Color pickers
  * Key-value fields
* Dynamically generates handles (ports) per node type

### 🔹 Execution Engine

* Custom **Graph Traverser**
* Builds an **Adjacency List**
* Detects entry points
* Traverses graph sequentially
* Supports branching (True / False paths)

---

## ✨ Key Features

* **Dynamic Node Library**

  * Nodes defined in backend
  * Auto-loaded into UI sidebar
  * Grouped by categories

* **Smart Validation**

  * Detects cycles (infinite loops)
  * Uses **Depth First Search (DFS)**

* **Multi-path Execution**

  * Condition nodes route data dynamically

* **Data Extraction**

  * Extract nested JSON using path strings
  * Example: `body.name`

* **AI Workflow Generator (Smart Mock)**

  * Built-in chat interface
  * Converts Arabic/English prompts into workflow JSON

* **Secure Execution**

  * Input sanitization
  * XSS prevention using regex validation

---

## 🛠️ Tech Stack

### Backend

* PHP 8.2+
* Laravel (latest version)
* SQLite (zero-config)

### Frontend

* React 18 + Vite
* React Flow
* TailwindCSS

---

## 🚀 Setup & Installation

### ✅ Prerequisites

* PHP >= 8.1 (with SQLite extension)
* Composer
* Node.js >= 18
* NPM

---

### 1️⃣ Backend Setup (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

📍 Backend runs on:
`http://127.0.0.1:8000`

---

### 2️⃣ Frontend Setup (React)

```bash
cd frontend
npm install
npm run dev
```

📍 Frontend runs on:
`http://localhost:5173`

> Note: Vite is pre-configured to proxy API requests to port 8000.

---

## 🧪 Demo Scenarios

### 🔹 Basic Execution

* Add a **Log node**
* Enter a message
* Click **Run**

---

### 🔹 Data Transformation

* Connect:
  `Log → Transform (Uppercase) → Color`
* Run to see styled output

---

### 🔹 API Integration

* Connect:

  * HTTP Request → `https://jsonplaceholder.typicode.com/users/1`
  * Transform → Extract (`body.name`)
  * Log
* Run to see live data extraction

---

### 🔹 Branching Logic

* Open AI Chat 🤖
* Enter:
  `"Make a condition if the word is admin"`
* Click **Apply to Canvas**
* Run workflow

---

### 🔹 Cycle Detection

* Connect output back to input
* System prevents execution with validation error

---

## 📂 Project Structure

```
backend/
├── app/
│   ├── Nodes/
│   │   ├── Contracts/
│   │   ├── Enums/
│   │   ├── LogNode.php
│   │   ├── ConditionNode.php
│   │   └── ...
│   ├── Services/
│   │   ├── GraphTraverser.php
│   │   ├── WorkflowValidator.php
│   │   └── AiService.php
│   └── Http/Controllers/

frontend/
├── src/
│   ├── nodes/
│   │   └── DynamicNode.jsx
│   ├── components/
│   │   └── AiChat.jsx
│   └── App.jsx
```

---

## 👤 Author

**Ehab Alshofee**
Computer Science / Software Engineering
