const taskForm = document.getElementById('task-form');
const taskList = document.getElementById('task-list');

// Fetch and display tasks on load
document.addEventListener('DOMContentLoaded', fetchTasks);

// Submit new task
taskForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const title = document.getElementById('title').value;
  const description = document.getElementById('description').value;
  const dueDate = document.getElementById('due-date').value;

  await fetch('api.php?action=create', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ title, description, due_date: dueDate })
  });

  taskForm.reset();
  fetchTasks();
});

// Fetch tasks
async function fetchTasks() {
  const res = await fetch('api.php?action=read');
  const tasks = await res.json();

  taskList.innerHTML = '';
  tasks.forEach(task => {
    const li = document.createElement('li');
    li.innerHTML = `
      <strong>${task.title}</strong><br>
      ${task.description}<br>
      Due: ${task.due_date}<br>
      <div class="task-buttons">
        <button onclick="editTask(${task.id}, '${task.title}', '${task.description}', '${task.due_date}')">Edit</button>
        <button onclick="deleteTask(${task.id})">Delete</button>
      </div>
    `;
    taskList.appendChild(li);
  });
}

// Delete task
async function deleteTask(id) {
  await fetch(api.php?action=delete&id=${id}, { method: 'DELETE' });
  fetchTasks();
}

// Edit task
function editTask(id, title, description, due_date) {
  document.getElementById('title').value = title;
  document.getElementById('description').value = description;
  document.getElementById('due-date').value = due_date;

  taskForm.onsubmit = async (e) => {
    e.preventDefault();
    const updatedTitle = document.getElementById('title').value;
    const updatedDescription = document.getElementById('description').value;
    const updatedDueDate = document.getElementById('due-date').value;

    await fetch(api.php?action=update&id=${id}, {
      method: 'PUT',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ title: updatedTitle, description: updatedDescription, due_date: updatedDueDate })
    });

    taskForm.reset();
    taskForm.onsubmit = originalSubmit;
    fetchTasks();
  }
}

// Save original form handler
const originalSubmit = taskForm.onsubmit;