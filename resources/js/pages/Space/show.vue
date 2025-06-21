<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import type { BreadcrumbItem } from '@/types'
import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

// UI components
import Heading from '@/components/Heading.vue'
import Card from '@/components/ui/card/Card.vue'
import CardHeader from '@/components/ui/card/CardHeader.vue'
import CardTitle from '@/components/ui/card/CardTitle.vue'
import CardDescription from '@/components/ui/card/CardDescription.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import CardFooter from '@/components/ui/card/CardFooter.vue'
import Modal from '@/components/ui/modal/Modal.vue'
import { ref, watch } from 'vue'

import Multiselect from 'vue-multiselect'
import 'vue-multiselect/dist/vue-multiselect.min.css'
import axios from 'axios'

const props = defineProps<{
    space: {
        id: number;
        name: string;
        slug: string;
        description: string;
        projects: { name: string; description: string; slug: string, status: string }[];
    };
    user: {
        role: 'admin' | 'user';
    };
}>()

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Spaces', href: '/spaces' },
    { title: props.space.name.charAt(0).toUpperCase() + props.space.name.slice(1), href: '' },
    { title: 'Projects', href: `/spaces/${props.space.slug}` },
]

const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)

const editingProject = ref<{ name: string; description: string; slug: string } | null>(null)
const deletingProjectSlug = ref<string | null>(null)

const form = ref({
    name: '',
    description: '',
    status: 'active', // valeur par défaut
    space_id: props.space?.id ?? null,
})

// ✅ CREATE
const submitCreateForm = () => {
    router.post('/projects', form.value, {
        onSuccess: () => {
            toast.success('Project created successfully 🎉')
            showCreateModal.value = false
            form.value = {
                name: '',
                description: '',
                status: 'active',
                space_id: props.space?.id ?? null,
            }
        },
        onError: () => toast.error('Error while creating the project ❌'),
    })
}

// ✅ EDIT
const submitEditForm = () => {
    if (editingProject.value) {
        router.put(`/projects/${editingProject.value.slug}`, form.value, {
            onSuccess: () => {
                toast.success('Project updated ✅')
                showEditModal.value = false
                form.value = { name: '', description: '', space_id: props.space?.id ?? null, status: 'active' }
                editingProject.value = null
            },
            onError: () => toast.error('Error while updating the project ❌'),
        })
    }
}

// ✅ DELETE
const confirmDelete = () => {
    if (deletingProjectSlug.value) {
        router.delete(`/projects/${deletingProjectSlug.value}`, {
            onSuccess: () => {
                toast.success('Project deleted 🗑️')
                showDeleteModal.value = false
                deletingProjectSlug.value = null
            },
            onError: () => toast.error('Error while deleting the project ❌'),
        })
    }
}

// Navigation
const goToProject = (spaceSlug: string, projectSlug: string) => {
    router.visit(`/spaces/${spaceSlug}/${projectSlug}`)
}

const editProject = (slug: string) => {
    const project = props.space.projects.find(p => p.slug === slug)
    if (project) {
        form.value = { name: project.name, description: project.description, space_id: props.space?.id ?? null, status: project.status }
        editingProject.value = { ...project }
        showEditModal.value = true
    }
}

const deleteProject = (slug: string) => {
    deletingProjectSlug.value = slug
    showDeleteModal.value = true
}

function statusBadgeClass(status: string) {
    switch (status) {
        case 'active':
            return 'bg-green-100 text-green-800';
        case 'archived':
            return 'bg-gray-200 text-gray-800';
        default:
            return 'bg-yellow-100 text-yellow-800';
    }
}

interface UserOption {
    id: number
    name: string
    email: string
}

const availableUsers = ref<UserOption[]>([])
const showShareModal = ref(false)

const shareForm = ref<{
    user_ids: number[]
}>({
    user_ids: []
})

const selectedUsers = ref<UserOption[]>([])


// Fetch users from server (called once when modal opens)
const fetchUsers = async () => {
    try {
        const res = await axios.get<UserOption[]>('/api/users') // returns array of users
        availableUsers.value = res.data
    } catch {
        toast.error('Failed to load users ❌')
    }
}

watch(showShareModal, (visible: boolean) => {
    if (visible && availableUsers.value.length === 0) {
        fetchUsers()
    }
})

const shareSpace = () => {
    const userIds = selectedUsers.value.map(user => user.id)
    router.post(`/spaces/${props.space.slug}/share`, { user_ids: userIds }, {
        onSuccess: () => {
            toast.success('Space shared successfully ✅')
            selectedUsers.value = []
            showShareModal.value = false
        },
        onError: () => toast.error('Error while sharing ❌')
    })
}



</script>

<template>

    <Head title="Space Details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-details-container p-6">
            <!-- Heading Section using the reusable Heading component -->
            <Heading :title="props.space.name" :description="props.space.description" />

            <!-- Project Cards Section -->
            <div class="flex flex-1 flex-col gap-4 p-4">
                <div class="flex justify-end">
                    <button @click="showCreateModal = true"
                        class="rounded bg-green-500 text-white px-4 py-2 hover:bg-green-600">
                        + Add project
                    </button>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">


                    <!-- Modals -->
                    <Modal v-model:modelValue="showCreateModal">
                        <h2 class="text-lg font-semibold mb-4">Create a new project</h2>
                        <form @submit.prevent="submitCreateForm" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium">Name</label>
                                <input v-model="form.name" type="text" required
                                    class="w-full rounded border px-3 py-2 mt-1" placeholder="Name" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Description</label>
                                <textarea v-model="form.description" class="w-full rounded border px-3 py-2 mt-1"
                                    placeholder="Description" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Status</label>
                                <select v-model="form.status" class="w-full rounded border px-3 py-2 mt-1">
                                    <option value="active">Active</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>

                            <div class="flex justify-end gap-2 mt-4">
                                <button type="button" @click="showCreateModal = false"
                                    class="text-sm px-4 py-2 border rounded">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="bg-blue-500 text-white text-sm px-4 py-2 rounded hover:bg-blue-600">
                                    Create
                                </button>
                            </div>
                        </form>
                    </Modal>

                    <Modal v-model:modelValue="showEditModal">
                        <h2 class="text-lg font-semibold mb-4">Update project</h2>
                        <form @submit.prevent="submitEditForm" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium">Name</label>
                                <input v-model="form.name" type="text" required
                                    class="w-full rounded border px-3 py-2 mt-1" placeholder="Name" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Description</label>
                                <textarea v-model="form.description" class="w-full rounded border px-3 py-2 mt-1"
                                    placeholder="Description" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium">Status</label>
                                <select v-model="form.status" class="w-full rounded border px-3 py-2 mt-1">
                                    <option value="active">Active</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>

                            <div class="flex justify-end gap-2 mt-4">
                                <button type="button" @click="showEditModal = false"
                                    class="text-sm px-4 py-2 border rounded">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="bg-blue-500 text-white text-sm px-4 py-2 rounded hover:bg-blue-600">
                                    Save
                                </button>
                            </div>
                        </form>
                    </Modal>
                    <Modal v-model:modelValue="showShareModal">
                        <h2 class="text-lg font-semibold mb-4">Share space with users</h2>
                        <form @submit.prevent="shareSpace" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium">Select Users</label>
                                <Multiselect v-model="selectedUsers" :options="availableUsers" :multiple="true"
                                    :close-on-select="false" placeholder="Search users..." track-by="id" label="email"
                                    :custom-label="(option: UserOption) => `${option.name} (${option.email})`" />
                            </div>
                            <div class="flex justify-end gap-2 mt-4">
                                <button type="button" @click="showShareModal = false"
                                    class="text-sm px-4 py-2 border rounded">Cancel</button>
                                <button type="submit"
                                    class="bg-blue-500 text-white text-sm px-4 py-2 rounded hover:bg-blue-600">Share</button>
                            </div>
                        </form>
                    </Modal>


                    <Modal v-model:modelValue="showDeleteModal">
                        <h2 class="text-lg font-semibold mb-4">Confirmer la suppression</h2>
                        <div class="flex justify-end gap-2 mt-4">
                            <button @click="showDeleteModal = false"
                                class="text-sm px-4 py-2 border rounded">Cancel</button>
                            <button @click="confirmDelete"
                                class="bg-red-500 text-white px-4 py-2 text-sm rounded hover:bg-red-600">
                                Delete
                            </button>
                        </div>
                    </Modal>
                    <Card v-for="project in props.space.projects" :key="project.slug"
                        class="cursor-pointer transition hover:shadow-lg"
                        @click="goToProject(props.space.slug, project.slug)">
                        <CardHeader>
                            <div class="flex justify-between items-center">
                                <div>
                                    <CardTitle>{{ project.name }}</CardTitle>
                                    <CardDescription>{{ project.description }}</CardDescription>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full"
                                    :class="statusBadgeClass(project.status)">
                                    {{ project.status }}
                                </span>
                            </div>
                        </CardHeader>

                        <CardContent>
                            <p class="text-gray-400">Explore more...</p>
                        </CardContent>
                        <CardFooter class="flex justify-end gap-2">
                            <button @click.stop="showShareModal = true"
                                class="rounded-md bg-purple-500 px-3 py-1 text-sm text-white hover:bg-purple-600">
                                Share
                            </button>

                            <button @click.stop="editProject(project.slug)"
                                class="rounded-md bg-blue-500 px-3 py-1 text-sm text-white hover:bg-blue-600">
                                Edit
                            </button>
                            <button @click.stop="deleteProject(project.slug)" :disabled="project.status === 'active'"
                                :class="[
                                    'rounded-md px-3 py-1 text-sm text-white',
                                    project.status === 'active'
                                        ? 'bg-red-300 cursor-not-allowed'
                                        : 'bg-red-500 hover:bg-red-600'
                                ]">
                                Delete
                            </button>

                        </CardFooter>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.space-details-container {
    max-width: 100%;
}

.space-heading {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 1.5rem;
}

.space-heading h1 {
    font-size: 2.5rem;
    font-weight: 600;
}

.space-heading p {
    font-size: 1.125rem;
    color: #555;
}

.space-heading .flex {
    justify-content: flex-start;
}

.space-content {
    margin-top: 20px;
}
</style>
