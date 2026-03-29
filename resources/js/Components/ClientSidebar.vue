<template>
    <div class="sidebar bg-gray-100 h-full w-64 p-4 flex flex-col border-r border-gray-200">
        <!-- Perfil Usuario Sidebar -->
        <div v-if="$page.props.auth?.user" class="flex flex-col items-center justify-center space-y-2 mb-8 p-4 bg-white rounded-lg shadow-sm border border-gray-100">
            <img :src="$page.props.auth.user.avatar_url" alt="Avatar del Cliente" class="w-20 h-20 rounded-full object-cover border-2 border-indigo-100">
            <div class="text-center">
                <p class="font-bold text-gray-800 text-sm">{{ $page.props.auth.user.name }}</p>
                <p class="text-xs text-gray-500 capitalize">{{ ($page.props.auth.user.role || 'cliente').replace('_', ' ') }}</p>
            </div>
        </div>

        <nav class="flex-1">
            <ul class="space-y-3">
                <li>
                    <Link
                        href="/panel/cliente"
                        class="block py-2 px-4 rounded transition-colors"
                        :class="isActive('/panel/cliente') ? 'bg-indigo-100 text-indigo-700 font-medium' : 'hover:bg-gray-200 text-gray-700'"
                    >
                        Panel de Reservas
                    </Link>
                </li>
                <li>
                    <Link
                        href="/panel/cliente/historial"
                        class="block py-2 px-4 rounded transition-colors"
                        :class="isActive('/panel/cliente/historial') ? 'bg-indigo-100 text-indigo-700 font-medium' : 'hover:bg-gray-200 text-gray-700'"
                    >
                        Mi Historial
                    </Link>
                </li>
                <li>
                    <Link
                        href="/perfil/editar"
                        class="block py-2 px-4 rounded transition-colors"
                        :class="isActive('/perfil/editar') ? 'bg-indigo-100 text-indigo-700 font-medium' : 'hover:bg-gray-200 text-gray-700'"
                    >
                        Configuraciones
                    </Link>
                </li>
                <li class="pt-8">
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="w-full text-left block py-2 px-4 rounded hover:bg-red-100 text-red-600 transition-colors"
                    >
                        Cerrar Sesión
                    </Link>
                </li>
            </ul>
        </nav>
    </div>
</template>

<script>
import { Link } from '@inertiajs/vue3';

export default {
    name: "ClientSidebar",
    components: {
        Link,
    },
    methods: {
        isActive(path) {
            if (path === '/panel/cliente') {
                return this.$page.url === path;
            }
            return this.$page.url.startsWith(path);
        }
    }
};
</script>
