<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { useOrganizationStore } from '@/entities/organization'

const organizationStore = useOrganizationStore()

let pollTimer: ReturnType<typeof setInterval> | null = null

function startPollingIfNeeded() {
  stopPolling()

  if (organizationStore.isBeingParsed) {
    pollTimer = setInterval(async () => {
      await organizationStore.fetchCurrent()
      if (!organizationStore.isBeingParsed) {
        stopPolling()
      }
    }, 3000)
  }
}

function stopPolling() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

onMounted(startPollingIfNeeded)
onUnmounted(stopPolling)
</script>

<template>
  <div v-if="!organizationStore.loaded">
    <p>Загрузка...</p>
  </div>

  <div v-else-if="!organizationStore.organization">
    <p>Ссылка на организацию ещё не сохранена.</p>
  </div>

  <div v-else>
    <h2>{{ organizationStore.organization.name || organizationStore.organization.yandex_url }}</h2>

    <p v-if="organizationStore.isBeingParsed">Идёт сбор данных, страница обновится автоматически...</p>

    <template v-if="organizationStore.organization.status === 'ready'">
      <p>Средний рейтинг: {{ organizationStore.organization.rating_avg }}</p>
      <p>Количество оценок: {{ organizationStore.organization.ratings_count }}</p>
      <p>Количество отзывов: {{ organizationStore.organization.reviews_count }}</p>
    </template>

    <p v-if="organizationStore.organization.status === 'failed'">
      Ошибка парсинга: {{ organizationStore.organization.last_error }}
    </p>
  </div>
</template>
