<template>
  <div class="auth-container">
    <h1 class="auth-title">Welcome Back</h1>
    <p class="auth-subtitle">Sign in to manage your tasks</p>

    <div v-if="errorMessage" class="alert-error">
      {{ errorMessage }}
    </div>

    <form @submit.prevent="handleLogin">
      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          class="form-input"
          placeholder="name@example.com"
          required
        />
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          class="form-input"
          placeholder="Enter your password"
          required
        />
      </div>

      <button type="submit" class="btn btn-primary" :disabled="loading">
        {{ loading ? 'Signing in...' : 'Sign In' }}
      </button>
    </form>

    <div class="auth-footer">
      Don't have an account?
      <router-link to="/register">Create an account</router-link>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const router = useRouter();
const loading = ref(false);
const errorMessage = ref('');

const form = reactive({
  email: '',
  password: '',
});

async function handleLogin() {
  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await api.post('/login', form);
    localStorage.setItem('auth_token', response.data.access_token);
    localStorage.setItem('auth_user', JSON.stringify(response.data.user));
    router.push('/tasks');
  } catch (error) {
    if (error.response && error.response.data && error.response.data.message) {
      errorMessage.value = error.response.data.message;
    } else {
      errorMessage.value = 'Failed to connect to server. Please try again.';
    }
  } finally {
    loading.value = false;
  }
}
</script>
