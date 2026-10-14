<template>
  <div class="container">
    <div class="tasks-header">
      <div>
        <h1 style="font-size: 1.5rem; font-weight: 700;">Task Dashboard</h1>
        <p style="color: var(--text-muted); font-size: 0.875rem;">Manage and track your project tasks</p>
      </div>
      <button class="btn btn-primary" style="width: auto;" @click="openCreateModal">
        Add New Task
      </button>
    </div>

    <!-- Alert / Messages -->
    <div v-if="successMessage" style="background-color: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 0.75rem; border-radius: 6px; font-size: 0.875rem; margin-bottom: 1rem;">
      {{ successMessage }}
    </div>
    <div v-if="errorMessage" class="alert-error">
      {{ errorMessage }}
    </div>

    <!-- Loading State -->
    <div v-if="loading" style="text-align: center; padding: 3rem; color: var(--text-muted);">
      Loading tasks...
    </div>

    <!-- Empty State -->
    <div v-else-if="tasks.length === 0" style="text-align: center; padding: 4rem 1rem; background: var(--bg-card); border: 1px solid var(--border); border-radius: 8px;">
      <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 0.5rem;">No tasks found</h3>
      <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;">Get started by creating your first task.</p>
      <button class="btn btn-primary" style="width: auto;" @click="openCreateModal">
        Create Task
      </button>
    </div>

    <!-- Task List -->
    <div v-else class="task-list">
      <div v-for="task in tasks" :key="task.id" class="task-card">
        <div class="task-info">
          <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.375rem;">
            <h3 class="task-title" :style="{ textDecoration: task.status === 'completed' ? 'line-through' : 'none' }">
              {{ task.title }}
            </h3>
            <span :class="['badge', `badge-${task.status}`]">
              {{ formatStatus(task.status) }}
            </span>
          </div>
          <p class="task-desc">
            {{ task.description || 'No description provided.' }}
          </p>
          <div style="font-size: 0.75rem; color: var(--text-muted);">
            Created: {{ formatDate(task.created_at) }}
          </div>
        </div>

        <div class="task-actions">
          <button
            class="btn btn-secondary btn-sm"
            @click="toggleStatus(task)"
            :title="task.status === 'completed' ? 'Mark as pending' : 'Mark as completed'"
          >
            {{ task.status === 'completed' ? 'Reopen' : 'Complete' }}
          </button>
          <button class="btn btn-secondary btn-sm" @click="openEditModal(task)">
            Edit
          </button>
          <button class="btn btn-danger btn-sm" @click="handleDelete(task.id)">
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Form (Create / Edit) -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-box">
        <h3 class="modal-title">{{ isEditing ? 'Edit Task' : 'Create New Task' }}</h3>

        <form @submit.prevent="handleSubmit">
          <div class="form-group">
            <label class="form-label" for="task-title">Title</label>
            <input
              id="task-title"
              v-model="modalForm.title"
              type="text"
              class="form-input"
              placeholder="Task title"
              required
            />
          </div>

          <div class="form-group">
            <label class="form-label" for="task-desc">Description</label>
            <textarea
              id="task-desc"
              v-model="modalForm.description"
              rows="3"
              class="form-textarea"
              placeholder="Task details and description (optional)"
            ></textarea>
          </div>

          <div class="form-group">
            <label class="form-label" for="task-status">Status</label>
            <select id="task-status" v-model="modalForm.status" class="form-select">
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
            </select>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn btn-secondary" @click="closeModal">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" style="width: auto;" :disabled="submitting">
              {{ submitting ? 'Saving...' : 'Save Task' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '@/services/api';

const tasks = ref([]);
const loading = ref(true);
const submitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const showModal = ref(false);
const isEditing = ref(false);
const editingTaskId = ref(null);

const modalForm = reactive({
  title: '',
  description: '',
  status: 'pending',
});

function formatStatus(status) {
  if (status === 'in_progress') return 'In Progress';
  if (status === 'completed') return 'Completed';
  return 'Pending';
}

function formatDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

async function fetchTasks() {
  loading.value = true;
  errorMessage.value = '';
  try {
    const response = await api.get('/tasks');
    tasks.value = response.data.data;
  } catch (error) {
    errorMessage.value = 'Failed to load tasks. Please try again.';
  } finally {
    loading.value = false;
  }
}

function openCreateModal() {
  isEditing.value = false;
  editingTaskId.value = null;
  modalForm.title = '';
  modalForm.description = '';
  modalForm.status = 'pending';
  showModal.value = true;
}

function openEditModal(task) {
  isEditing.value = true;
  editingTaskId.value = task.id;
  modalForm.title = task.title;
  modalForm.description = task.description || '';
  modalForm.status = task.status;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

async function handleSubmit() {
  submitting.value = true;
  errorMessage.value = '';
  successMessage.value = '';

  try {
    if (isEditing.value) {
      const response = await api.put(`/tasks/${editingTaskId.value}`, modalForm);
      const index = tasks.value.findIndex((t) => t.id === editingTaskId.value);
      if (index !== -1) {
        tasks.value[index] = response.data.data;
      }
      successMessage.value = 'Task updated successfully.';
    } else {
      const response = await api.post('/tasks', modalForm);
      tasks.value.unshift(response.data.data);
      successMessage.value = 'Task created successfully.';
    }
    closeModal();
    setTimeout(() => {
      successMessage.value = '';
    }, 4000);
  } catch (error) {
    if (error.response && error.response.data && error.response.data.message) {
      errorMessage.value = error.response.data.message;
    } else {
      errorMessage.value = 'Failed to save task.';
    }
  } finally {
    submitting.value = false;
  }
}

async function toggleStatus(task) {
  const newStatus = task.status === 'completed' ? 'pending' : 'completed';
  try {
    const response = await api.put(`/tasks/${task.id}`, { status: newStatus });
    task.status = response.data.data.status;
  } catch (error) {
    alert('Failed to update task status.');
  }
}

async function handleDelete(id) {
  if (!confirm('Are you sure you want to delete this task?')) return;

  try {
    await api.delete(`/tasks/${id}`);
    tasks.value = tasks.value.filter((t) => t.id !== id);
    successMessage.value = 'Task deleted successfully.';
    setTimeout(() => {
      successMessage.value = '';
    }, 4000);
  } catch (error) {
    alert('Failed to delete task.');
  }
}

onMounted(() => {
  fetchTasks();
});
</script>
