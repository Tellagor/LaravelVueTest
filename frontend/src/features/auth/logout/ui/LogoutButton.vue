<script setup lang="ts">
import { useSessionStore } from '@/entities/session'
import { sessionApi } from '@/entities/session/api/sessionApi'

const emit = defineEmits<{ success: [] }>()

const sessionStore = useSessionStore()

async function handleClick() {
  await sessionApi.logout()
  sessionStore.setUser(null)
  emit('success')
}
</script>

<template>
  <button type="button" @click="handleClick">Выйти</button>
</template>
