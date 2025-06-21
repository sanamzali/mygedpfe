<script setup lang="ts">
import { defineProps } from 'vue';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import DynamicLucideIcon from '@/components/ui/icon/DynamicLucideIcon.vue';
import FileList from './FileList.vue';

const props = defineProps<{
    folder: {
        id: number;
        name: string;
        description: string;
        slug: string;
        icon: string;
        status: string;
        parent_id: number | null;
    };
    folders: Array<{
        id: number;
        name: string;
        description: string;
        slug: string;
        icon: string;
        status: string;
        parent_id: number | null;
    }>;
    onEdit: (slug: string) => void;
    onDelete: (slug: string) => void;
    onNavigate: (slug: string) => void;
}>();

// const getSubfolders = (parentId: number) =>
//     props.folders.filter(f => f.parent_id === parentId);
</script>

<template>
    <div>
        <Card class="cursor-pointer transition hover:shadow-lg" @click="onNavigate(folder.slug)">
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
                <button @click.stop="onEdit(folder.slug)"
                    class="rounded-md bg-blue-500 px-3 py-1 text-sm text-white hover:bg-blue-600">
                    Edit
                </button>
                <button @click.stop="onDelete(folder.slug)"
                    class="rounded-md bg-red-500 px-3 py-1 text-sm text-white hover:bg-red-600">
                    Delete
                </button>
            </CardFooter>
        </Card>



        <!-- <div v-if="getSubfolders(folder.id).length > 0" class="ml-4">
            <FolderTree v-for="subfolder in getSubfolders(folder.id)" :key="subfolder.slug"
                :folder="subfolder" :folders="folders" :onEdit="onEdit" :onDelete="onDelete" :onNavigate="onNavigate" />
        </div> -->
    </div>
</template>
