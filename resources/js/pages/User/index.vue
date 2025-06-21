<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import Modal from '@/components/ui/modal/Modal.vue'

const props = defineProps<{
    users: { id: number; name: string; email: string; role: 'admin' | 'user' }[]
    user: { role: 'admin' | 'user' }
}>()

const breadcrumbs = [{ title: 'Users', href: '/users' }]

const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)

const editingUser = ref<{ id: number; name: string; email: string; role: string } | null>(null)
const deletingUserId = ref<number | null>(null)
const form = ref({
    name: '',
    email: '',
    role: 'user',
})

// Pagination
const currentPage = ref(1)
const pageSize = ref(10) // Number of users per page
const totalPages = computed(() => Math.ceil(props.users.length / pageSize.value))

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value
    const end = start + pageSize.value
    return props.users.slice(start, end)
})

const goToPage = (page: number) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
    }
}

// Create
const submitCreateForm = () => {
    router.post('/users', form.value, {
        onSuccess: () => {
            toast.success('User created successfully 🎉')
            showCreateModal.value = false
            form.value = { name: '', email: '', role: '' }
            currentPage.value = 1
        },
        onError: () => toast.error('Error while creating user ❌'),
    })
}

// Edit
const submitEditForm = () => {
    if (editingUser.value) {
        router.put(`/users/${editingUser.value.id}`, form.value, {
            onSuccess: () => {
                toast.success('User updated ✅')
                showEditModal.value = false
                editingUser.value = null
                form.value = { name: '', email: '', role: 'user' }
            },
            onError: () => toast.error('Error while updating user ❌'),
        })
    }
}

// Delete
const confirmDelete = () => {
    if (deletingUserId.value) {
        router.delete(`/users/${deletingUserId.value}`, {
            onSuccess: () => {
                toast.success('User deleted 🗑️')
                showDeleteModal.value = false
                deletingUserId.value = null
                // Adjust page if last item on page was deleted
                if (paginatedUsers.value.length === 0 && currentPage.value > 1) {
                    currentPage.value--
                }
            },
            onError: () => toast.error('Error while deleting user ❌'),
        })
    }
}

const editUser = (id: number) => {
    const user = props.users.find(u => u.id === id)
    if (user) {
        form.value = { name: user.name, email: user.email, role: user.role }
        editingUser.value = { ...user }
        showEditModal.value = true
    }
}

const deleteUser = (id: number) => {
    deletingUserId.value = id
    showDeleteModal.value = true
}
</script>

<template>

    <Head title="Users" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">
            <div class="flex justify-end">
                <button @click="showCreateModal=true"
                    class="rounded bg-green-500 text-white px-4 py-2 hover:bg-green-600">
                    + Add user
                </button>
            </div>

            <!-- Create Modal -->
            <Modal v-model:modelValue="showCreateModal">
                <h2 class="text-lg font-semibold mb-4">Create new user</h2>
                <form @submit.prevent="submitCreateForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Name</label>
                        <input v-model="form.name" type="text" required class="w-full border rounded px-3 py-2 mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Email</label>
                        <input v-model="form.email" type="email" required
                            class="w-full border rounded px-3 py-2 mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Role</label>
                        <select v-model="form.role" class="w-full border rounded px-3 py-2 mt-1">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showCreateModal = false"
                            class="border rounded px-4 py-2 text-sm">Cancel</button>
                        <button type="submit"
                            class="bg-blue-500 text-white px-4 py-2 text -*sm rounded hover:bg-blue-600">Create</button>
                    </div>
                </form>
            </Modal>

            <!-- Edit Modal -->
            <Modal v-model:modelValue="showEditModal">
                <h2 class="text-lg font-semibold mb-4">Edit user</h2>
                <form @submit.prevent="submitEditForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Name</label>
                        <input v-model="form.name" type="text" required class="w-full border rounded px-3 py-2 mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Email</label>
                        <input v-model="form.email" type="email" required
                            class="w-full border rounded px-3 py-2 mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Role</label>
                        <select v-model="form.role" class="w-full border rounded px-3 py-2 mt-1">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showEditModal = false"
                            class="border rounded px-4 py-2 text-sm">Cancel</button>
                        <button type="submit"
                            class="bg-blue-500 text-white px-4 py-2 text-sm rounded hover:bg-blue-600">Save</button>
                    </div>
                </form>
            </Modal>

            <!-- Delete Modal -->
            <Modal v-model:modelValue="showDeleteModal">
                <h2 class="text-lg font-semibold mb-4">Confirm deletion</h2>
                <p class="mb-4">Are you sure you want to delete this user?</p>
                <div class="flex justify-end gap-2">
                    <button @click="showDeleteModal = false" class="border rounded px-4 py-2 text-sm">Cancel</button>
                    <button @click="confirmDelete"
                        class="bg-red-500 text-white px-4 py-2 text-sm rounded hover:bg-red-600">Delete</button>
                </div>
            </Modal>

            <!-- User Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-300 rounded">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Name</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Email</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Role</th>
                            <th v-if="props.user.role === 'user'"
                                class="px-6 py-3 text-right text-sm font-medium text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="user in paginatedUsers" :key="user.id">
                            <td class="px-6 py-4 text-sm text-gray-900">{{ user.name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ user.email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 capitalize">{{ user.role }}</td>
                            <td v-if="props.user.role === 'admin'" class="px-6 py-4 text-right">
                                <button @click="editUser(user.id)"
                                    class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600 mr-2">Edit</button>
                                <button @click="deleteUser(user.id)"
                                    class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div class="flex items-center justify-between mt-4">
                <div class="text-sm text-gray-600">
                    Showing {{ (currentPage - 1) * pageSize + 1 }} to {{ Math.min(currentPage * pageSize,
                    props.users.length) }} of {{ props.users.length }} users
                </div>
                <div class="flex gap-2">
                    <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                        class="px-3 py-1 border rounded text-sm"
                        :class="{ 'bg-gray-200 cursor-not-allowed': currentPage === 1, 'hover:bg-gray-100': currentPage !== 1 }">
                        Previous
                    </button>
                    <button v-for="page in totalPages" :key="page" @click="goToPage(page)"
                        class="px-3 py-1 border rounded text-sm"
                        :class="{ 'bg-blue-500 text-white': currentPage === page, 'hover:bg-gray-100': currentPage !== page }">
                        {{ page }}
                    </button>
                    <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                        class="px-3 py-1 border rounded text-sm"
                        :class="{ 'bg-gray-200 cursor-not-allowed': currentPage === totalPages, 'hover:bg-gray-100': currentPage !== totalPages }">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
