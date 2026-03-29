<template>
    <AppShell>
        <div class="flex flex-col md:flex-row gap-6 items-start">
            <div class="w-full md:w-64 flex-shrink-0 sticky top-24">
                <ClientSidebar class="rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden" />
            </div>
            <div class="flex-1 min-w-0 w-full">
                <div class="bg-white dark:bg-slate-900/50 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-6">Configuraciones de Perfil</h1>
        <form @submit.prevent="submit">
            <div class="mb-4">
                <label
                    for="name"
                    class="block text-sm font-medium text-gray-700"
                    >Nombre</label
                >
                <input
                    v-model="form.name"
                    type="text"
                    id="name"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
            </div>

            <div class="mb-6 flex items-start space-x-4">
                <div class="flex-shrink-0 mt-2">
                    <img v-if="previewUrl || user.avatar_url" :src="previewUrl || user.avatar_url" class="h-20 w-20 rounded-full object-cover border border-gray-200">
                    <div v-else class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center">
                        <span class="text-gray-500 text-xs">Sin foto</span>
                    </div>
                </div>
                <div class="flex-1">
                    <label
                        for="avatar"
                        class="block text-sm font-medium text-gray-700 mb-1"
                        >Cambiar Avatar</label
                    >
                    <input
                        type="file"
                        id="avatar"
                        accept="image/png, image/jpeg, image/webp"
                        @change="handleAvatarChange"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                    />
                    <p class="mt-2 text-xs text-gray-500">JPG, PNG o WEBP. Máximo 2MB.</p>
                </div>
            </div>

            <button
                type="submit"
                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                Guardar Cambios
            </button>
        </form>
                </div>
            </div>
        </div>
    </AppShell>
</template>

<script>
import { ref } from 'vue';
import { useForm } from "@inertiajs/vue3";
import AppShell from "@/Components/AppShell.vue";
import ClientSidebar from "@/Components/ClientSidebar.vue";

export default {
    components: {
        AppShell,
        ClientSidebar,
    },
    props: {
        user: Object,
    },
    setup(props) {
        const previewUrl = ref(null);
        const form = useForm({
            name: props.user.name,
            avatar: null,
        });

        const handleAvatarChange = (event) => {
            const file = event.target.files[0];
            if (file) {
                form.avatar = file;
                previewUrl.value = URL.createObjectURL(file);
            }
        };

        const submit = () => {
            form.post("/perfil/editar", {
                forceFormData: true,
                preserveScroll: true,
            });
        };

        return { form, handleAvatarChange, submit, previewUrl };
    },
};
</script>

<style scoped>
.profile-edit {
    max-width: 600px;
    margin: 0 auto;
    padding: 1rem;
}
</style>
