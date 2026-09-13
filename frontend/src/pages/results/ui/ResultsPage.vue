<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useOrganizationStore } from '@/entities/organization'
import { OrganizationStatus } from '@/widgets/organization-status'
import { ReviewsList } from '@/widgets/reviews-list'
import { LogoutButton } from '@/features/auth/logout'

const organizationStore = useOrganizationStore()
const router = useRouter()

onMounted(() => {
  organizationStore.fetchCurrent()
})

function handleLogout() {
  router.push({ name: 'login' })
}
</script>

<template>
  <div>
    <nav>
      <RouterLink :to="{ name: 'settings' }">Настройки</RouterLink>
      <LogoutButton @success="handleLogout" />
    </nav>

    <h1>Итоговая страница</h1>

    <OrganizationStatus />

    <p v-if="organizationStore.loaded && !organizationStore.organization">
      <RouterLink :to="{ name: 'settings' }">Добавить ссылку на организацию</RouterLink>
    </p>

    <ReviewsList />
  </div>
</template>
