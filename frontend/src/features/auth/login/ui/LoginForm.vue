<script setup lang="ts">
import { ref } from 'vue'
import { useSessionStore } from '@/entities/session'
import { sessionApi } from '@/entities/session/api/sessionApi'

const emit = defineEmits<{ success: [] }>()

const email = ref('test@example.com')
const password = ref('password')
const error = ref('')
const loading = ref(false)

const sessionStore = useSessionStore()

async function handleSubmit() {
  error.value = ''
  loading.value = true

  try {
    const user = await sessionApi.login(email.value, password.value)
    sessionStore.setUser(user)
    sessionStore.initialized = true
    emit('success')
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Не удалось войти'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form @submit.prevent="handleSubmit">
    <div>
      <label for="email">Email</label>
      <input id="email" v-model="email" type="email" required />
    </div>
    <div>
      <label for="password">Пароль</label>
      <input id="password" v-model="password" type="password" required />
    </div>
    <button type="submit" :disabled="loading">
      {{ loading ? 'Вход...' : 'Войти' }}
    </button>
    <p v-if="error">{{ error }}</p>
  </form>
</template>
