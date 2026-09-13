<script setup lang="ts">
import { useRouter, RouterLink } from 'vue-router'
import { SaveOrganizationLinkForm } from '@/features/save-organization-link'
import { LogoutButton } from '@/features/auth/logout'

const router = useRouter()

function handleSuccess() {
  router.push({ name: 'results' })
}

function handleLogout() {
  router.push({ name: 'login' })
}
</script>

<template>
  <div>
    <nav>
      <RouterLink :to="{ name: 'results' }">Итоговая страница</RouterLink>
      <LogoutButton @success="handleLogout" />
    </nav>

    <h1>Настройки</h1>
    <SaveOrganizationLinkForm @success="handleSuccess" />
  </div>
</template>
