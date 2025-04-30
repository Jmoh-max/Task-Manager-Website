let tasks = [];

function addTask() {
    let taskInput = document.getElementById('taskInput');
    let taskText = taskInput.value.trim();

    if (taskText !== "") {
        tasks.push(taskText);
        taskInput.value = "";
        renderTasks();
    }
}

function renderTasks() {
    let taskList = document.getElementById('taskList');
    taskList.innerHTML = "";

    tasks.forEach((task, index) => {
        let li = document.createElement('li');
        li.innerHTML = `
            ${task}
            <button class="edit-btn" onclick="editTask(${index})">Edit</button>
            <button class="delete-btn" onclick="deleteTask(${index})">Delete</button>
        `;
        taskList.appendChild(li);
    });
}

function editTask(index) {
    let newTask = prompt("Edit the task:", tasks[index]);
    if (newTask !== null && newTask.trim() !== "") {
        tasks[index] = newTask.trim();
        renderTasks();
    }
}

function deleteTask(index) {
    if (confirm("Are you sure you want to delete this task?")) {
        tasks.splice(index, 1);
        renderTasks();
    }
}
