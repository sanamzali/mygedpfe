<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

// UI + Layouts
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import Card from '@/components/ui/card/Card.vue'
import CardHeader from '@/components/ui/card/CardHeader.vue'
import CardTitle from '@/components/ui/card/CardTitle.vue'
import CardDescription from '@/components/ui/card/CardDescription.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import CardFooter from '@/components/ui/card/CardFooter.vue'
import Modal from '@/components/ui/modal/Modal.vue'

// Props
const props = defineProps<{
  spaces: { name: string; slug: string; description: string }[]
  user: { role: 'admin' | 'user' }
}>()

const breadcrumbs = [{ title: 'Spaces', href: '/spaces' }]

// State
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editingSpace = ref<{ name: string; description: string; slug: string } | null>(null)
const deletingSpaceSlug = ref<string | null>(null)
const form = ref({
  name: '',
  description: '',
})

// Create
const submitCreateForm = () => {
  router.post('/spaces', form.value, {
    onSuccess: () => {
      toast.success('Space created successfully 🎉')
      showCreateModal.value = false
      form.value = { name: '', description: '' }
    },
    onError: () => toast.error('Error while creating the space ❌'),
  })
}

// Edit
const submitEditForm = () => {
  if (editingSpace.value) {
    router.put(`/spaces/${editingSpace.value.slug}`, form.value, {
      onSuccess: () => {
        toast.success('Space updated ✅')
        showEditModal.value = false
        form.value = { name: '', description: '' }
        editingSpace.value = null
      },
      onError: () => toast.error('Error while updating the space ❌'),
    })
  }
}

// Delete
const confirmDelete = () => {
  if (deletingSpaceSlug.value) {
    router.delete(`/spaces/${deletingSpaceSlug.value}`, {
      onSuccess: () => {
        toast.success('Space deleted 🗑️')
        showDeleteModal.value = false
        deletingSpaceSlug.value = null
      },
      onError: () => toast.error('Error while deleting the space ❌'),
    })
  }
}

// Navigation
const goToSpace = (spaceSlug: string) => router.visit(`/spaces/${spaceSlug}`)

const editSpace = (slug: string) => {
  const space = props.spaces.find(s => s.slug === slug)
  if (space) {
    form.value = { name: space.name, description: space.description }
    editingSpace.value = { ...space }
    showEditModal.value = true
  }
}

const deleteSpace = (slug: string) => {
  deletingSpaceSlug.value = slug
  showDeleteModal.value = true
}
</script>

<template>
  <Head title="Spaces" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-4 p-4">
      <div class="flex justify-end">
        <button @click="showCreateModal = true" class="rounded bg-green-500 text-white px-4 py-2 hover:bg-green-600">
            + Add space
        </button>
      </div>

      <!-- Modals -->
      <Modal v-model:modelValue="showCreateModal">
        <h2 class="text-lg font-semibold mb-4">Create a new space</h2>
        <form @submit.prevent="submitCreateForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium">Name</label>
            <input v-model="form.name" type="text" required class="w-full rounded border px-3 py-2 mt-1" placeholder="Name"/>
          </div>
          <div>
            <label class="block text-sm font-medium">Description</label>
            <textarea v-model="form.description" class="w-full rounded border px-3 py-2 mt-1" placeholder="Description" />
          </div>
          <div class="flex justify-end gap-2 mt-4">
            <button type="button" @click="showCreateModal = false" class="text-sm px-4 py-2 border rounded">
                Cancel
            </button>
            <button type="submit" class="bg-blue-500 text-white text-sm px-4 py-2 rounded hover:bg-blue-600">
                Create
            </button>
          </div>
        </form>
      </Modal>

      <Modal v-model:modelValue="showEditModal">
        <h2 class="text-lg font-semibold mb-4">Update Space</h2>
        <form @submit.prevent="submitEditForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium">Name</label>
            <input v-model="form.name" type="text" required class="w-full rounded border px-3 py-2 mt-1" placeholder="Name" />
          </div>
          <div>
            <label class="block text-sm font-medium">Description</label>
            <textarea v-model="form.description" class="w-full rounded border px-3 py-2 mt-1" placeholder="Description" />
          </div>
          <div class="flex justify-end gap-2 mt-4">
            <button type="button" @click="showEditModal = false" class="text-sm px-4 py-2 border rounded">
                Cancel
            </button>
            <button type="submit" class="bg-blue-500 text-white text-sm px-4 py-2 rounded hover:bg-blue-600">
                Save
            </button>
          </div>
        </form>
      </Modal>

      <Modal v-model:modelValue="showDeleteModal">
        <h2 class="text-lg font-semibold mb-4">Confirmer la suppression</h2>
        <div class="flex justify-end gap-2 mt-4">
          <button @click="showDeleteModal = false" class="text-sm px-4 py-2 border rounded">Cancel</button>
          <button @click="confirmDelete" class="bg-red-500 text-white px-4 py-2 text-sm rounded hover:bg-red-600">
            Delete
          </button>
        </div>
      </Modal>

      <!-- Card List -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <Card v-for="space in props.spaces" :key="space.slug" class="cursor-pointer hover:shadow"
          @click="goToSpace(space.slug)">
          <CardHeader>
            <CardTitle>{{ space.name }}</CardTitle>
            <CardDescription>{{ space.description }}</CardDescription>
          </CardHeader>
          <CardContent>
            <p class="text-gray-400">Explore more...</p>
          </CardContent>
          <CardFooter v-if="props.user.role === 'admin'" class="flex justify-end gap-2">
            <button @click.stop="editSpace(space.slug)" class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                Update
            </button>
            <button @click.stop="deleteSpace(space.slug)" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                Delete
            </button>
          </CardFooter>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
