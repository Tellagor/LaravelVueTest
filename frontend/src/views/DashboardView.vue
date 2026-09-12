<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div>
    <h1>Итоговая страница</h1>
    <p v-if="auth.user">Вы вошли как {{ auth.user.name }} ({{ auth.user.email }})</p>
    <button @click="handleLogout">Выйти</button>
  </div>
</template>
