<template>
  <div id="app">
    <nav class="navbar">
      <router-link to="/" class="navbar-brand">
        Task Manager
      </router-link>

      <div v-if="isAuthenticated" class="navbar-user">
        <span style="font-size: 0.875rem; color: var(--text-muted);">
          {{ userEmail }}
        </span>
        <button class="btn btn-secondary btn-sm" @click="handleLogout">
          Sign Out
        </button>
      </div>

      <div v-else class="navbar-user">
        <router-link to="/login" class="btn btn-secondary btn-sm">
          Sign In
        </router-link>
        <router-link to="/register" class="btn btn-primary btn-sm" style="width: auto;">
          Sign Up
        </router-link>
      </div>
    </nav>

    <main style="flex: 1;">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const router = useRouter();

const isAuthenticated = computed(() => {
  return !!localStorage.getItem('auth_token');
});

const userEmail = computed(() => {
  const user = localStorage.getItem('auth_user');
  if (user) {
    try {
      return JSON.parse(user).email;
    } catch {
      return '';
    }
  }
  return '';
});

async function handleLogout() {
  try {
    await api.post('/logout');
  } catch {
    // Ignore error if token is already expired
  } finally {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
    router.push('/login');
  }
}
</script>
