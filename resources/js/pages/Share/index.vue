<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import 'vue3-toastify/dist/index.css'

// UI + Layouts
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import Card from '@/components/ui/card/Card.vue'
import CardHeader from '@/components/ui/card/CardHeader.vue'
import CardTitle from '@/components/ui/card/CardTitle.vue'
import CardDescription from '@/components/ui/card/CardDescription.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
// Props
const props = defineProps<{
    spaces: { name: string; slug: string; description: string }[]
    projects: { name: string; slug: string; description: string }[]
    folders: { name: string; slug: string; description: string }[]
    files: { filename: string; slug: string }[]
    user: { role: 'admin' | 'user' }
}>()

const breadcrumbs = [{ title: 'Shared with me', href: '/shared' }]

// Navigation
const goToSpace = (spaceSlug: string) => router.visit(`/spaces/${spaceSlug}`)

</script>

<template>

    <Head title="Shared with me" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Shared Spaces -->
                <div>
                    <h1 class="text-2xl font-semibold mb-4">Shared Spaces</h1>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Card v-for="space in props.spaces" :key="space.slug" class="cursor-pointer hover:shadow"
                            @click="goToSpace(space.slug)">
                            <CardHeader>
                                <CardTitle>{{ space.name }}</CardTitle>
                                <CardDescription>{{ space.description }}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <p class="text-gray-400">Explore more...</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Shared Projects -->
                <div>
                    <h1 class="text-2xl font-semibold mb-4">Shared Projects</h1>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Card v-for="space in props.projects" :key="space.slug + '-project'"
                            class="cursor-pointer hover:shadow" @click="goToSpace(space.slug)">
                            <CardHeader>
                                <CardTitle>{{ space.name }}</CardTitle>
                                <CardDescription>{{ space.description }}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <p class="text-gray-400">Explore more...</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Shared Folders -->
                <div>
                    <h1 class="text-2xl font-semibold mb-4">Shared Folders</h1>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Card v-for="space in props.folders" :key="space.slug + '-folder'"
                            class="cursor-pointer hover:shadow" @click="goToSpace(space.slug)">
                            <CardHeader>
                                <CardTitle>{{ space.name }}</CardTitle>
                                <CardDescription>{{ space.description }}</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <p class="text-gray-400">Explore more...</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Shared Files -->
                <div>
                    <h1 class="text-2xl font-semibold mb-4">Shared Files</h1>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <Card v-for="space in props.files" :key="space.slug + '-file'"
                            class="cursor-pointer hover:shadow" @click="goToSpace(space.slug)">
                            <CardHeader>
                                <CardTitle>{{ space.filename }}</CardTitle>
                                <!-- <CardDescription>{{ space.description }}</CardDescription> -->
                            </CardHeader>
                            <CardContent>
                                <p class="text-gray-400">Explore more...</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
