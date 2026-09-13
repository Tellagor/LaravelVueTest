<script setup lang="ts">
import { ref } from 'vue'
import { useOrganizationStore } from '@/entities/organization'

const emit = defineEmits<{ success: [] }>()

const yandexUrl = ref('')
const error = ref('')
const loading = ref(false)

const organizationStore = useOrganizationStore()

async function handleSubmit() {
  error.value = ''
  loading.value = true

  try {
    await organizationStore.save(yandexUrl.value)
    emit('success')
  } catch (e: any) {
    error.value =
      e.response?.data?.errors?.yandex_url?.[0] || e.response?.data?.message || 'Не удалось сохранить ссылку'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form @submit.prevent="handleSubmit">
    <div>
      <label for="yandex_url">Ссылка на карточку организации в Яндекс.Картах</label>
      <input
        id="yandex_url"
        v-model="yandexUrl"
        type="url"
        placeholder="https://yandex.ru/maps/org/название/123456789/"
        required
      />
    </div>
    <button type="submit" :disabled="loading">
      {{ loading ? 'Сохранение...' : 'Сохранить' }}
    </button>
    <p v-if="error">{{ error }}</p>
  </form>
</template>
