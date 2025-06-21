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
import { ref } from 'vue'
import DynamicLucideIcon from '@/components/ui/icon/DynamicLucideIcon.vue'

// Props from Laravel (to bind data dynamically)
const props = defineProps<{
    space: {
        id: number;
        name: string;
        description: string;
        slug: string;
    };
    project: {
        id: number;
        name: string;
        description: string;
        slug: string;
        folders: {
            id: number;
            name: string;
            description: string;
            slug: string;
            icon: string;
            status: string;
            parent_id: number | null;
        }[];
    };
}>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Spaces', href: '/spaces' },
    { title: props.space.name, href: `/spaces/${props.space.slug}` },
    { title: 'Projects', href: `/spaces/${props.space.slug}` },
    { title: props.project.name, href: '' },
    { title: 'Folders', href: `/spaces/${props.space.slug}` },
];

// State
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editingFolder = ref<{ name: string; description: string; slug: string } | null>(null)
const deletingFolderSlug = ref<string | null>(null)

const form = ref({
    name: '',
    description: '',
    icon: '',
    status: '',
    space_id: props.space?.id ?? null,
    project_id: props.project?.id ?? null,
})

const iconOptions = [
    'Folder',
    'File',
    'Home',
    'Archive',
    'Book',
    'Box',
    'Database',
    'FileText',
    'HardDrive',
    'Image',
    'Inbox',
    'Package',
    'Settings',
    'Star',
    'Trash',
    'Upload',
    'User',
]

// ✅ CREATE Folder
const submitCreateForm = () => {
    router.post(`/folders`, form.value, {
        onSuccess: () => {
            toast.success('Folder created successfully 🎉')
            showCreateModal.value = false
            form.value = {
                name: '',
                description: '',
                icon: '',
                status: 'Active',
                space_id: props.space?.id ?? null,
                project_id: props.project?.id ?? null,
            }
        },
        onError: () => toast.error('Error while creating the folder ❌'),
    })
}

// ✅ EDIT Folder
const submitEditForm = () => {
    if (editingFolder.value) {
        router.put(`/folders/${editingFolder.value.slug}`, form.value, {
            onSuccess: () => {
                toast.success('Folder updated ✅')
                showEditModal.value = false
                form.value = {
                    name: '',
                    space_id: props.space?.id ?? null,
                    project_id: props.project?.id ?? null,
                    description: '',
                    icon: '',
                    status: 'Active',
                }
                editingFolder.value = null
            },
            onError: () => toast.error('Error while updating the folder ❌'),
        })
    }
}

// ✅ DELETE Folder
const confirmDelete = () => {
    if (deletingFolderSlug.value) {
        router.delete(`/folders/${deletingFolderSlug.value}`, {
            onSuccess: () => {
                toast.success('Folder deleted 🗑️')
                showDeleteModal.value = false
                deletingFolderSlug.value = null
            },
            onError: () => toast.error('Error while deleting the folder ❌'),
        })
    }
}

// Navigation
const goToFolder = (spaceSlug: string, projectSlug: string, folderSlug: string) => {
    router.visit(`/spaces/${spaceSlug}/${projectSlug}/${folderSlug}`)
}

// ✅ EDIT Folder modal data loader
const editFolder = (slug: string) => {
    const folder = props.project.folders.find(f => f.slug === slug)
    if (folder) {
        form.value = {
            name: folder.name,
            description: folder.description,
            icon: folder.icon,
            status: folder.status, // Or `folder.status` if available
            space_id: props.space?.id ?? null,
            project_id: props.project?.id ?? null,
        }
        editingFolder.value = { ...folder }
        showEditModal.value = true
    }
}

const deleteFolder = (slug: string) => {
    deletingFolderSlug.value = slug
    showDeleteModal.value = true
}
</script>

<template>

    <Head title="Space Details" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-details-container p-6">
            <!-- Heading Section using the reusable Heading component -->
            <Heading :title="props.project.name" :description="props.project.description" />

            <!-- Project Cards Section -->
            <div class="flex flex-1 flex-col gap-4 p-4">
                <div class="flex justify-end">
                    <button @click="showCreateModal = true"
                        class="rounded bg-green-500 text-white px-4 py-2 hover:bg-green-600">
                        + Add folder
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                    <!-- Modals -->
                    <Modal v-model:modelValue="showCreateModal">
                        <h2 class="text-lg font-semibold mb-4">Create a new folder</h2>
                        <form @submit.prevent="submitCreateForm" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium">Name</label>
                                <input v-model="form.name" type="text" required
                                    class="w-full rounded border px-3 py-2 mt-1" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Description</label>
                                <textarea v-model="form.description" class="w-full rounded border px-3 py-2 mt-1" />
                            </div>

                            <div class="mt-2 flex items-center gap-4">
                                <div v-if="form.icon" mx-2>
                                    <DynamicLucideIcon :name="form.icon" size="24" />
                                </div>
                                <select v-model="form.icon" class="w-full rounded border px-3 py-2 mt-1">
                                    <option disabled value="">Select an icon</option>
                                    <option v-for="icon in iconOptions" :key="icon" :value="icon">
                                        {{ icon }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Status</label>
                                <input v-model="form.status" type="text" class="w-full rounded border px-3 py-2 mt-1"
                                    placeholder="e.g. active, archived" />
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
                        <h2 class="text-lg font-semibold mb-4">Update folder</h2>
                        <form @submit.prevent="submitEditForm" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium">Name</label>
                                <input v-model="form.name" type="text" required
                                    class="w-full rounded border px-3 py-2 mt-1" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Description</label>
                                <textarea v-model="form.description" class="w-full rounded border px-3 py-2 mt-1" />
                            </div>

                            <div class="mt-2 flex items-center gap-4">
                                <div v-if="form.icon" mx-2>
                                    <DynamicLucideIcon :name="form.icon" size="24" />
                                </div>
                                <select v-model="form.icon" class="w-full rounded border px-3 py-2 mt-1">
                                    <option disabled value="">Select an icon</option>
                                    <option v-for="icon in iconOptions" :key="icon" :value="icon">
                                        {{ icon }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium">Status</label>
                                <input v-model="form.status" type="text" class="w-full rounded border px-3 py-2 mt-1"
                                    placeholder="e.g. active, archived" />
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

                    <Modal v-model:modelValue="showDeleteModal">
                        <h2 class="text-lg font-semibold mb-4">Confirmer la suppression</h2>
                        <div class="flex justify-end gap-2 mt-4">
                            <button @click="showDeleteModal = false" class="text-sm px-4 py-2 border rounded">
                                Cancel
                            </button>
                            <button @click="confirmDelete"
                                class="bg-red-500 text-white px-4 py-2 text-sm rounded hover:bg-red-600">
                                Delete
                            </button>
                        </div>
                    </Modal>

                    <Card v-for="folder in props.project.folders" :key="folder.slug"
                        class="cursor-pointer transition hover:shadow-lg"
                        @click="goToFolder(props.space.slug, props.project.slug, folder.slug)">
                        <CardHeader>
                            <CardTitle>
                                <div class="flex items-center gap-2">
                                    <DynamicLucideIcon :name="folder.icon" size="32" />
                                    {{ folder.name }}
                                </div>
                            </CardTitle>
                            <CardDescription>{{ folder.description }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p class="text-gray-400">Explore more...</p>
                        </CardContent>
                        <CardFooter class="flex justify-end gap-2">
                            <button @click.stop="editFolder(folder.slug)"
                                class="rounded-md bg-blue-500 px-3 py-1 text-sm text-white hover:bg-blue-600">
                                Edit
                            </button>
                            <button @click.stop="deleteFolder(folder.slug)"
                                class="rounded-md bg-red-500 px-3 py-1 text-sm text-white hover:bg-red-600">
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
