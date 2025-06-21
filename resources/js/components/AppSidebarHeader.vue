<script setup lang="ts">
import { ref, watch } from 'vue'
import axios from 'axios'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import { SidebarTrigger } from '@/components/ui/sidebar'
import type { BreadcrumbItemType } from '@/types'

const props = defineProps<{
  breadcrumbs?: BreadcrumbItemType[]
}>()

const searchQuery = ref('')
const results = ref<any[]>([])
const isLoading = ref(false)

// Gestion du debounce pour éviter les requêtes trop fréquentes
let debounceTimeout: number | null = null

watch(searchQuery, async (newQuery) => {
  if (debounceTimeout) clearTimeout(debounceTimeout)
  if (!newQuery.trim()) {
    results.value = []
    return
  }

  isLoading.value = true
  debounceTimeout = setTimeout(async () => {
    try {
      const response = await axios.get('/api/search', {
        params: { q: newQuery }
      })
      results.value = response.data
    } catch (error) {
      console.error('Erreur lors de la recherche :', error)
    } finally {
      isLoading.value = false
    }
  }, 300)
})

const handleResultClick = (file: any) => {
  if (!file.file_path) return
  window.open(window.location.origin + file.file_path, '_blank')
}
</script>

<template>
  <header
    class="flex items-center justify-between h-16 px-4 md:px-6 border-b bg-white border-sidebar-border/70 shadow-sm transition-all">
    <!-- Partie gauche : Sidebar trigger + fil d'Ariane -->
    <div class="flex items-center gap-3">
      <SidebarTrigger class="-ml-2" />
      <Breadcrumbs v-if="breadcrumbs?.length" :breadcrumbs="breadcrumbs" />
    </div>

    <!-- Partie droite : Barre de recherche -->
    <div class="relative w-[300px] max-w-md group/search">
      <input v-model="searchQuery" type="text" placeholder="search..." aria-label="search"
        class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow duration-200 ease-in-out text-sm bg-gray-50" />

      <!-- Icône Loupe -->
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
        class="absolute left-3 top-2.5 h-4 w-4 text-gray-400 group-focus-within:text-blue-500 transition-colors">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>

      <!-- Résultats déroulants -->
      <transition name="slide-down">
        <div v-show="results.length > 0"
          class="absolute right-0 mt-2 w-full max-h-64 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-xl z-50">
          <ul>
            <li v-for="(result, index) in results" :key="index"
              class="px-4 py-3 hover:bg-blue-50 cursor-pointer transition-colors flex items-start gap-3"
              @click="handleResultClick(result._source)">
              <!-- Icône du fichier -->
              <div class="shrink-0 flex items-center justify-center w-8 h-8 rounded-md">
                <span class="text-xs font-bold" :class="{
                  'bg-blue-100 text-blue-600': result._source.file_type?.includes('pdf'),
                  'bg-green-100 text-green-600': result._source.file_type?.includes('csv'),
                  'bg-red-100 text-red-600': result._source.file_type?.includes('docx') || result._source.file_type?.includes('wordprocessingml'),
                  'bg-yellow-100 text-yellow-600': result._source.file_type?.includes('xlsx'),
                  'bg-gray-100 text-gray-600': !result._source.file_type
                }">
                  {{
                    result._source.file_type
                      ? (
                        result._source.file_type.includes('pdf') ? 'PDF' :
                          result._source.file_type.includes('csv') ? 'CSV' :
                            result._source.file_type.includes('docx') || result._source.file_type.includes('wordprocessingml') ?
                              'DOC' :
                              result._source.file_type.includes('xlsx') ? 'XLSX' :
                  'File'
                  )
                  : 'File'
                  }}
                </span>
              </div>
              <div class="truncate">
                <p class="font-medium truncate">{{ result._source.filename }}</p>
                <p class="text-xs text-gray-500">
                  {{ result._source.file_type }} • {{ new Date(result._source.created_at).toLocaleDateString() }}
                </p>
              </div>
            </li>
          </ul>
        </div>
      </transition>

      <!-- Loader -->
      <div v-if="isLoading" class="absolute right-3 top-2.5 animate-spin h-4 w-4 text-blue-500">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10" stroke-dasharray="60" stroke-dashoffset="0" />
        </svg>
      </div>
    </div>
  </header>
</template>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.2s ease;
  transform: translateY(0);
  opacity: 1;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>