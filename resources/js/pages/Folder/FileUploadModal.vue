<template>
    <Modal v-model:modelValue="showUploadModal">
        <h2 class="text-lg font-semibold mb-4">Upload a File</h2>
        <form @submit.prevent="submitUploadForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium">Select File</label>
                <input type="file" @change="handleFileChange" required class="w-full rounded border px-3 py-2 mt-1" />
            </div>
            <div>
                <label class="block text-sm font-medium">Description</label>
                <textarea v-model="form.description" class="w-full rounded border px-3 py-2 mt-1" />
            </div>
            <div>
                <label class="flex items-center space-x-2">
                    <input type="checkbox" v-model="form.is_encrypted" />
                    <span class="text-sm">Encrypt this file</span>
                </label>
                <input v-if="form.is_encrypted" type="password" v-model="form.password" placeholder="Enter password"
                    class="block w-full px-3 py-2 border rounded mt-2" />
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" @click="showUploadModal = false" class="text-sm px-4 py-2 border rounded">
                    Cancel
                </button>
                <button type="submit" :disabled="isUploading"
                    class="bg-blue-500 text-white text-sm px-4 py-2 rounded hover:bg-blue-600">
                    {{ isUploading ? 'Uploading...' : 'Upload' }}
                </button>
            </div>
        </form>
    </Modal>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue3-toastify';
import Modal from '@/components/ui/modal/Modal.vue';

const props = defineProps<{
    folderId: number;
    projectId: number;
    spaceId: number;
}>();

const showUploadModal = ref(false);
const isUploading = ref(false);
const form = ref({
    file: null as File | null,
    description: '',
    is_encrypted: false,
    password: ''
});

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.value.file = target.files[0];
    }
};

const submitUploadForm = () => {
    isUploading.value = true;
    const formData = new FormData();

    if (form.value.file) {
        formData.append('file', form.value.file);
    }
    formData.append('folder_id', props.folderId.toString());
    formData.append('project_id', props.projectId.toString());
    formData.append('space_id', props.spaceId.toString());
    formData.append('description', form.value.description);
    formData.append('is_encrypted', form.value.is_encrypted ? '1' : '0');
    if (form.value.is_encrypted) {
        formData.append('password', form.value.password);
    }

    router.post('/files/upload', formData, {
        forceFormData: true,
        onSuccess: () => {
            toast.success('File uploaded successfully 🎉');
            showUploadModal.value = false;
            form.value = {
                file: null,
                description: '',
                is_encrypted: false,
                password: ''
            };
            router.visit(window.location.href);
        },
        onError: (errors) => {
            console.error(errors);
            toast.error(
                Object.values(errors).flat().join('\n') || 'Error while uploading the file ❌'
            );
        },
        onFinish: () => {
            isUploading.value = false;
        }
    });
};

defineExpose({ showUploadModal });
</script>
