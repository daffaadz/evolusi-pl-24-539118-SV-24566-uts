<template>
  <div class="auth-container">
    <h1 class="auth-title">Create an Account</h1>
    <p class="auth-subtitle">Sign up to get started with task management</p>

    <div v-if="errorMessage" class="alert-error">
      {{ errorMessage }}
    </div>

    <form @submit.prevent="handleRegister">
      <div class="form-group">
        <label class="form-label" for="name">Full Name</label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          class="form-input"
          placeholder="Your full name"
          required
        />
      </div>

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
          placeholder="At least 8 characters"
          required
          minlength="8"
        />
      </div>

      <div class="form-group">
        <label class="form-label" for="password_confirmation">Confirm Password</label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          class="form-input"
          placeholder="Repeat your password"
          required
        />
      </div>

      <button type="submit" class="btn btn-primary" :disabled="loading">
        {{ loading ? 'Creating account...' : 'Sign Up' }}
      </button>
    </form>

    <div class="auth-footer">
      Already have an account?
      <router-link to="/login">Sign in</router-link>
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
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

async function handleRegister() {
  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await api.post('/register', form);
    localStorage.setItem('auth_token', response.data.access_token);
    localStorage.setItem('auth_user', JSON.stringify(response.data.user));
    router.push('/tasks');
  } catch (error) {
    if (error.response && error.response.data) {
      if (error.response.data.errors) {
        // Validation errors array
        const errorList = Object.values(error.response.data.errors).flat();
        errorMessage.value = errorList.join(', ');
      } else if (error.response.data.message) {
        errorMessage.value = error.response.data.message;
      }
    } else {
      errorMessage.value = 'Failed to create account. Please try again.';
    }
  } finally {
    loading.value = false;
  }
}
</script>
