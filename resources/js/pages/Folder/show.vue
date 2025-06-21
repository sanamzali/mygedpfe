<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import type { BreadcrumbItem } from '@/types'
import { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

// UI components
import Heading from '@/components/Heading.vue'

import Modal from '@/components/ui/modal/Modal.vue'
import { ref, computed } from 'vue'
import DynamicLucideIcon from '@/components/ui/icon/DynamicLucideIcon.vue'
import FolderTree from './FolderTree.vue' // Import the FolderTree component
import FileUploadModal from './FileUploadModal.vue'
import FileList from './FileList.vue'



// Props from Laravel
const props = defineProps<{
    user: { role: 'admin' | 'user' };
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
        folders: Array<{
            id: number;
            name: string;
            description: string;
            slug: string;
            icon: string;
            status: string;
            parent_id: number | null;
        }>;
    };
    folder: {
        id: number;
        name: string;
        description: string;
        slug: string;
        icon: string;
        status: string;
        parent_id: number | null;
    };
}>();

// Dynamic Breadcrumbs
const buildBreadcrumbs = (): BreadcrumbItem[] => {
    const crumbs: BreadcrumbItem[] = [
        { title: 'Spaces', href: '/spaces' },
        { title: props.space.name, href: `/spaces/${props.space.slug}` },
        { title: 'Projects', href: `/spaces/${props.space.slug}` },
        { title: props.project.name, href: `/spaces/${props.space.slug}/${props.project.slug}` },
    ];

    const folderCrumbs: BreadcrumbItem[] = [];
    let currentFolder = props.folder;
    while (currentFolder) {
        folderCrumbs.unshift({
            title: currentFolder.name,
            href: `/spaces/${props.space.slug}/${props.project.slug}/${currentFolder.slug}`,
        });
        const parent = props.project.folders.find(f => f.id === currentFolder.parent_id);
        currentFolder = parent || null;
    }

    return [...crumbs, ...folderCrumbs];
};

const breadcrumbs = computed(() => buildBreadcrumbs());


// State
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editingFolder = ref<{ name: string; description: string; slug: string } | null>(null);
const deletingFolderSlug = ref<string | null>(null);

// Form state
const form = ref({
    name: '',
    description: '',
    icon: '',
    status: 'Active',
    space_id: props.space?.id ?? null,
    project_id: props.project?.id ?? null,
    parent_id: props.folder?.id ?? null, // Initialize parent_id with the current folder's ID
});

const iconOptions = [
    'Folder', 'File', 'Home', 'Archive', 'Book', 'Box',
    'Database', 'FileText', 'HardDrive', 'Image', 'Inbox',
    'Package', 'Settings', 'Star', 'Trash', 'Upload', 'User',
];

// Available parent folders (exclude self and descendants)
const availableParentFolders = computed(() =>
    props.project.folders.filter(
        f => f.id !== props.folder.id && !isDescendant(f.id, props.folder.id)
    )
);

const isDescendant = (folderId: number, potentialAncestorId: number): boolean => {
    const folder = props.project.folders.find(f => f.id === folderId);
    if (!folder || folder.parent_id === null) return false;
    if (folder.parent_id === potentialAncestorId) return true;
    return isDescendant(folder.parent_id, potentialAncestorId);
};

// Create Folder
const submitCreateForm = () => {
    router.post(`/folders`, form.value, {
        onSuccess: () => {
            toast.success('Folder created successfully 🎉');
            showCreateModal.value = false;
            form.value = {
                name: '',
                description: '',
                icon: '',
                status: 'Active',
                space_id: props.space?.id ?? null,
                project_id: props.project?.id ?? null,
                parent_id: props.folder?.id ?? null,
            };
        },
        onError: () => toast.error('Error while creating the folder ❌'),
    });
};

// Edit Folder
const submitEditForm = () => {
    if (editingFolder.value) {
        router.put(`/folders/${editingFolder.value.slug}`, form.value, {
            onSuccess: () => {
                toast.success('Folder updated ✅');
                showEditModal.value = false;
                form.value = {
                    name: '',
                    description: '',
                    icon: '',
                    status: 'Active',
                    space_id: props.space?.id ?? null,
                    project_id: props.project?.id ?? null,
                    parent_id: props.folder?.id ?? null,
                };
                editingFolder.value = null;
            },
            onError: () => toast.error('Error while updating the folder ❌'),
        });
    }
};

// Delete Folder
const confirmDelete = () => {
    if (deletingFolderSlug.value) {
        const folder = props.project.folders.find(f => f.slug === deletingFolderSlug.value);
        const hasSubfolders = props.project.folders.some(f => f.parent_id === folder?.id);
        if (hasSubfolders) {
            toast.error('Cannot delete folder with subfolders. Delete subfolders first.');
            return;
        }
        router.delete(`/folders/${deletingFolderSlug.value}`, {
            onSuccess: () => {
                toast.success('Folder deleted 🗑️');
                showDeleteModal.value = false;
                deletingFolderSlug.value = null;
            },
            onError: () => toast.error('Error while deleting the folder ❌'),
        });
    }
};

// Navigation
const goToFolder = (slug: string) => router.visit(`/spaces/${props.space.slug}/${props.project.slug}/${slug}`);

// Edit Folder modal data loader
const editFolder = (slug: string) => {
    const folder = props.project.folders.find(f => f.slug === slug);
    if (folder) {
        form.value = {
            name: folder.name,
            description: folder.description,
            icon: folder.icon,
            status: folder.status,
            space_id: props.space?.id ?? null,
            project_id: props.project?.id ?? null,
            parent_id: folder.parent_id ?? null,
        };
        editingFolder.value = { ...folder };
        showEditModal.value = true;
    }
};

// Delete Folder
const deleteFolder = (slug: string) => {
    deletingFolderSlug.value = slug;
    showDeleteModal.value = true;
};

// Get subfolders for a given folder
const getSubfolders = (parentId: number) =>
    props.project.folders.filter(f => f.parent_id === parentId);

// Function to open the create modal
const openCreateModal = () => {
    form.value = {
        name: '',
        description: '',
        icon: '',
        status: 'Active',
        space_id: props.space?.id ?? null,
        project_id: props.project?.id ?? null,
        parent_id: props.folder?.id ?? null, // Ensure parent_id is set to the current folder's ID
    };
    showCreateModal.value = true;
};


const fileUploadModal = ref<InstanceType<typeof FileUploadModal> | null>(null);

const openUploadModal = () => {
    if (fileUploadModal.value) {
        fileUploadModal.value.showUploadModal = true;
    }
};

</script>

<template>

    <Head title="Folder Details" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-details-container p-6">
            <!-- Heading Section -->
            <Heading :title="props.folder.name" :description="props.folder.description" />

            <!-- Folder Path
            <div class="text-sm text-gray-600 mb-4">
                Path: {{ folderPath }}
            </div> -->

            <!-- Folder Cards Section -->
            <div class="flex flex-1 flex-col gap-4 p-4">
                <div class="flex justify-end">
                    <button @click="openCreateModal"
                        class="rounded bg-green-500 text-white px-4 py-2 hover:bg-green-600">
                        + Add Folder
                    </button>
                    <button @click="openUploadModal"
                        class="rounded bg-blue-500 text-white px-4 py-2 hover:bg-blue-600 ml-2">
                        + Upload File
                    </button>
                </div>

                <!-- Display subfolders -->
                <div v-if="getSubfolders(props.folder.id).length === 0" class="text-center text-gray-500">
                    No subfolders available. Create a new folder to get started!
                </div>
                <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                    <FolderTree v-for="folder in getSubfolders(props.folder.id)" :key="folder.slug" :folder="folder"
                        :folders="props.project.folders" :onEdit="editFolder" :onDelete="deleteFolder"
                        :onNavigate="goToFolder" />


                </div>
                <FileList :folderId="props.folder.id" :userRole="props.user.role" class="mt-4" />

            </div>

            <!-- Modals -->
            <Modal v-model:modelValue="showCreateModal">
                <h2 class="text-lg font-semibold mb-4">Create a new folder</h2>
                <form @submit.prevent="submitCreateForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Name</label>
                        <input v-model="form.name" type="text" required class="w-full rounded border px-3 py-2 mt-1" />
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
                        <button type="button" @click="showCreateModal = false" class="text-sm px-4 py-2 border rounded">
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
                        <input v-model="form.name" type="text" required class="w-full rounded border px-3 py-2 mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Description</label>
                        <textarea v-model="form.description" class="w-full rounded border px-3 py-2 mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Parent Folder</label>
                        <select v-model="form.parent_id" class="w-full rounded border px-3 py-2 mt-1">
                            <option :value="null">No Parent (Top-Level)</option>
                            <option v-for="folder in availableParentFolders" :key="folder.id" :value="folder.id">
                                {{ folder.name }}
                            </option>
                        </select>
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
                        <button type="button" @click="showEditModal = false" class="text-sm px-4 py-2 border rounded">
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
                <h2 class="text-lg font-semibold mb-4">Confirm Deletion</h2>
                <p class="text-sm text-gray-600 mb-4">
                    Are you sure you want to delete this folder? This action cannot be undone.
                </p>
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
            <FileUploadModal ref="fileUploadModal" :folderId="props.folder.id" :projectId="props.project.id"
                :spaceId="props.space.id" />
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
