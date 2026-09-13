<script setup lang="ts">
import { useRouter } from 'vue-router'
import { LoginForm } from '@/features/auth/login'

const router = useRouter()

function handleSuccess() {
  router.push({ name: 'results' })
}
</script>

<template>
  <div>
    <h1>Вход</h1>
    <LoginForm @success="handleSuccess" />
  </div>
</template>
