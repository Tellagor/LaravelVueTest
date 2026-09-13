<script setup lang="ts">
import { ref, watch } from 'vue'
import { useOrganizationStore } from '@/entities/organization'
import { reviewApi } from '@/entities/review'
import type { Review } from '@/entities/review'

const organizationStore = useOrganizationStore()

const reviews = ref<Review[]>([])
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const loading = ref(false)
const error = ref('')

async function loadPage(page: number) {
  loading.value = true
  error.value = ''

  try {
    const response = await reviewApi.fetchPage(page)
    reviews.value = response.data
    currentPage.value = response.meta.current_page
    lastPage.value = response.meta.last_page
    total.value = response.meta.total
  } catch {
    error.value = 'Не удалось загрузить отзывы'
  } finally {
    loading.value = false
  }
}

watch(
  () => organizationStore.organization?.status,
  (status) => {
    if (status === 'ready') {
      loadPage(1)
    }
  },
  { immediate: true },
)

function goToPage(page: number) {
  if (page >= 1 && page <= lastPage.value) {
    loadPage(page)
  }
}
</script>

<template>
  <div v-if="organizationStore.organization?.status === 'ready'">
    <h3>Отзывы ({{ total }})</h3>

    <p v-if="loading">Загрузка отзывов...</p>
    <p v-if="error">{{ error }}</p>

    <table v-if="!loading && reviews.length > 0">
      <thead>
        <tr>
          <th>Автор</th>
          <th>Оценка</th>
          <th>Текст</th>
          <th>Дата</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="review in reviews" :key="review.id">
          <td>{{ review.author_name }}</td>
          <td>{{ review.rating }}</td>
          <td>{{ review.text }}</td>
          <td>{{ review.published_at }}</td>
        </tr>
      </tbody>
    </table>

    <p v-if="!loading && reviews.length === 0">Отзывов пока нет.</p>

    <div v-if="lastPage > 1">
      <button type="button" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
        Назад
      </button>
      <span>Страница {{ currentPage }} из {{ lastPage }}</span>
      <button type="button" :disabled="currentPage >= lastPage" @click="goToPage(currentPage + 1)">
        Вперёд
      </button>
    </div>
  </div>
</template>
