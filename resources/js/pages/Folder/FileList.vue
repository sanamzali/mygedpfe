<template>
    <div>
        <div v-if="files.data.length === 0" class="text-center text-gray-500">
            No files available. Upload a file to get started!
        </div>
        <div v-else>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Filename
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            File Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Size
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Encrypted
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Latest Version
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="file in files.data" :key="file.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.filename }}
                            <span v-if="file.versions && file.versions.length > 1" title="Has multiple versions">
                                📄
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ file.file_type }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatFileSize(file.file_size)
                            }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ file.is_encrypted ? 'Yes' :
                            'No' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <template v-if="file.versions && file.versions.length">
                                v{{ file.versions[file.versions.length - 1].version_number }}
                            </template>
                            <template v-else>
                                v1
                            </template>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 flex gap-3">
                            <button @click="handleAction(file, 'download')"
                                class="text-blue-600 hover:text-blue-900">Download</button>
                            <button @click="handleAction(file, 'update')"
                                class="text-yellow-600 hover:text-yellow-900">Update</button>
                            <button @click="handleAction(file, 'history')"
                                class="text-green-600 hover:text-green-900">History</button>
                            <button v-if="props.userRole === 'admin'" @click="handleAction(file, 'delete')"
                                class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="mt-4 flex justify-center">
                <button @click="fetchFiles(files.prev_page_url)" :disabled="!files.prev_page_url"
                    class="px-4 py-2 mx-1 bg-gray-200 rounded disabled:opacity-50">
                    Previous
                </button>
                <span class="px-4 py-2">Page {{ files.current_page }} of {{ files.last_page }}</span>
                <button @click="fetchFiles(files.next_page_url)" :disabled="!files.next_page_url"
                    class="px-4 py-2 mx-1 bg-gray-200 rounded disabled:opacity-50">
                    Next
                </button>
            </div>
        </div>

        <!-- Delete Modal -->
        <Modal v-model:modelValue="showDeleteFileModal">
            <h2 class="text-lg font-semibold mb-4">Confirm File Deletion</h2>
            <p class="text-sm text-gray-600 mb-4">Are you sure you want to delete <strong>{{ deletingFile?.filename
                    }}</strong>?
            </p>
            <div class="flex justify-end gap-2 mt-4">
                <button @click="showDeleteFileModal = false" class="text-sm px-4 py-2 border rounded">Cancel</button>
                <button @click="deleteFile"
                    class="bg-red-500 text-white px-4 py-2 text-sm rounded hover:bg-red-600">Delete</button>
            </div>
        </Modal>

        <!-- Update Modal -->
        <Modal v-model:modelValue="showUpdateModal">
            <h2 class="text-lg font-semibold mb-4">Upload New Version</h2>
            <p class="text-sm text-gray-600 mb-4">Upload a new version for <strong>{{ updatingFile?.filename
                    }}</strong>.</p>
            <input type="file" @change="uploadNewVersion" class="mb-4 block w-full" />
            <div class="flex justify-end gap-2">
                <button @click="showUpdateModal = false" class="text-sm px-4 py-2 border rounded">Cancel</button>
            </div>
        </Modal>

        <!-- History Modal -->
        <Modal v-model:modelValue="showHistoryModal">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold">File History</h2>
                <button @click="showHistoryModal = false" class="text-sm text-gray-500 hover:text-black">✕</button>
            </div>
            <ul>
                <li v-for="version in versionHistory" :key="version.id"
                    class="flex justify-between items-center border-b py-2">
                    <div>
                        <p class="font-medium">
                            Version {{ version.version_number }} ({{ formatFileSize(version.file_size) }})
                        </p>
                        <p class="text-xs text-gray-500">Type: {{ version.type }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a :href="`/api/files/version/${version.id}/download`" target="_blank"
                            class="text-blue-600 hover:underline text-sm">
                            Download
                        </a>
                        <span v-if="version.is_active" class="text-green-600 font-semibold text-sm">
                            Active
                        </span>
                        <button v-else @click="restoreVersion(version.id)"
                            class="text-orange-600 hover:underline text-sm">
                            Rewind
                        </button>
                    </div>
                </li>
            </ul>
        </Modal>
        <!-- Password Modal -->
        <Modal v-model:modelValue="showPasswordModal">
            <h2 class="text-lg font-semibold mb-4">Enter Password</h2>
            <p class="text-sm text-gray-600 mb-4">This file is encrypted. Please enter the password to continue.</p>
            <input v-model="enteredPassword" type="password" class="w-full px-3 py-2 border rounded mb-4"
                placeholder="Password" />
            <div class="flex justify-end gap-2">
                <button @click="cancelPasswordModal" class="text-sm px-4 py-2 border rounded">Cancel</button>
                <button @click="confirmPasswordModal"
                    class="text-sm px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Confirm</button>
            </div>
        </Modal>
    </div>
</template>




<script setup lang="ts">
import { ref, onMounted, watch, defineExpose } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/components/ui/modal/Modal.vue';
import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

const props = defineProps<{
    folderId: number;
    userRole: string;
}>();

interface File {
    id: number;
    filename: string;
    file_type: string;
    file_size: number;
    is_encrypted: boolean;
    versions?: FileVersion[];
}

interface PaginatedFiles {
    data: File[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface FileVersion {
    id: number;
    version_number: number;
    type: string;
    file_size: number;
    path: string;
    is_active: boolean;
    is_final: boolean;
}

const files = ref<PaginatedFiles>({
    data: [],
    current_page: 1,
    last_page: 1,
    prev_page_url: null,
    next_page_url: null,
});

const showDeleteFileModal = ref(false);
const showUpdateModal = ref(false);
const showHistoryModal = ref(false);
const showPasswordModal = ref(false);

const deletingFile = ref<File | null>(null);
const updatingFile = ref<File | null>(null);
const versionHistory = ref<FileVersion[]>([]);
const pendingAction = ref<null | { file: File; type: string }>(null);
const enteredPassword = ref('');
const handleAction = (file: File, action: 'download' | 'update' | 'history' | 'delete') => {
    if (file.is_encrypted) {
        pendingAction.value = { file, type: action };
        showPasswordModal.value = true;
        return;
    }
    executeAction(file, action);
};

const executeAction = (file: File, action: string) => {
    switch (action) {
        case 'download':
            downloadFile(file);
            break;
        case 'update':
            startFileUpdate(file);
            break;
        case 'history':
            fetchFileVersions(file);
            break;
        case 'delete':
            confirmDeleteFile(file);
            break;
    }
};

const confirmPasswordModal = () => {
    if (!enteredPassword.value) {
        toast.error('Password is required');
        return;
    }
    if (pendingAction.value) {
        showPasswordModal.value = false;
        const { file, type } = pendingAction.value;
        executeAction(file, type);
        pendingAction.value = null;
        enteredPassword.value = '';
    }
};

const cancelPasswordModal = () => {
    showPasswordModal.value = false;
    enteredPassword.value = '';
    pendingAction.value = null;
};

const fetchFiles = async (url: string | null = null) => {
    try {
        const endpoint = url || `/files/folder/${props.folderId}`;
        const response = await fetch(endpoint);
        files.value = await response.json();
    } catch (error) {
        console.error('Error fetching files:', error);
    }
};

const confirmDeleteFile = (file: File) => {
    deletingFile.value = file;
    showDeleteFileModal.value = true;
};

const deleteFile = () => {
    if (!deletingFile.value) return;

    router.delete(`/files/${deletingFile.value.id}`, {
        onSuccess: () => {
            toast.success('File deleted successfully ✅');
            fetchFiles();
            showDeleteFileModal.value = false;
            deletingFile.value = null;
        },
        onError: () => {
            toast.error('Error deleting file ❌');
        },
        preserveScroll: true,
    });
};

const startFileUpdate = (file: File) => {
    updatingFile.value = file;
    showUpdateModal.value = true;
};

const uploadNewVersion = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const selectedFile = input.files?.[0];
    if (!selectedFile || !updatingFile.value) return;

    const formData = new FormData();
    formData.append('file', selectedFile);
    formData.append('original_file_id', String(updatingFile.value.id));

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    try {
        const response = await fetch('/files/version', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token || '',
            },
            body: formData,
        });

        if (response.ok) {
            toast.success('New version uploaded successfully ✅');
            showUpdateModal.value = false;
            updatingFile.value = null;
            fetchFiles();
        } else {
            toast.error('Failed to upload version ❌');
        }
    } catch (error) {
        console.error('Upload error:', error);
        toast.error('Error occurred during upload ❌');
    }
};

const fetchFileVersions = async (file: File) => {
    try {
        const response = await fetch(`/api/files/${file.id}/versions`);
        versionHistory.value = await response.json();
        showHistoryModal.value = true;
    } catch {
        toast.error('Failed to fetch version history');
    }
};

const restoreVersion = async (versionId: number) => {
    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch(`/files/version/${versionId}/restore`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': token || '',
                'Accept': 'application/json',
            },
        });

        if (response.ok) {
            toast.success('Version restored ✅');
            showHistoryModal.value = false;
            fetchFiles();
        } else {
            toast.error('Failed to restore version ❌');
        }
    } catch (err) {
        console.error(err);
        toast.error('Error occurred while restoring ❌');
    }
};

const downloadFile = (file: File) => {
    window.open(`/files/download/${file.id}`, '_blank');
};

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

onMounted(fetchFiles);
watch(() => props.folderId, () => fetchFiles());
defineExpose({ fetchFiles });
</script>
