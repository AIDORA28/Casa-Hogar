<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

// Obtener page props de Inertia correctamente
const page = usePage();

// Obtener role del usuario desde props de Inertia
const userRole = computed(() => page.props.auth?.user?.role || '');
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-50">
            <nav class="border-b border-green-100 bg-white shadow-sm">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')" class="flex items-center">
                                    <span class="text-2xl font-bold text-green-600">🏠</span>
                                    <span class="ml-2 text-xl font-bold text-gray-800">Casa Hogar</span>
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                    class="inline-flex items-center"
                                >
                                    <span class="mr-2">🏠</span> Dashboard
                                </NavLink>
                                
                                <NavLink
                                    :href="route('daily-registry')"
                                    :active="route().current('daily-registry')"
                                    class="inline-flex items-center"
                                >
                                    <span class="mr-2">📖</span> Registro Diario
                                </NavLink>
                                
                                <NavLink
                                    v-if="$page.props.auth.permissions && $page.props.auth.permissions.includes('manage_inventory')"
                                    :href="route('inventory')"
                                    :active="route().current('inventory')"
                                    class="inline-flex items-center"
                                >
                                    <span class="mr-2">📦</span> Inventario
                                </NavLink>
                                
                                <NavLink
                                    v-if="$page.props.auth.permissions && $page.props.auth.permissions.includes('download_reports')"
                                    :href="route('reports')"
                                    :active="route().current('reports')"
                                    class="inline-flex items-center"
                                >
                                    <span class="mr-2">📊</span> Reportes
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="56">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-xl border border-gray-100 bg-white px-4 py-2 text-sm font-bold text-gray-700 transition-all hover:bg-gray-50 hover:border-green-200 focus:outline-none shadow-sm group"
                                            >
                                                <div class="flex items-center">
                                                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-green-100 to-indigo-100 flex items-center justify-center mr-2 text-lg shadow-inner group-hover:scale-110 transition-transform">
                                                        👤
                                                    </div>
                                                    <div class="text-left">
                                                        <p class="text-xs font-black text-gray-900 leading-tight uppercase tracking-tighter">{{ page.props.auth.user.name }}</p>
                                                        <p class="text-[10px] font-bold" :class="userRole === 'admin' ? 'text-green-600' : 'text-blue-600'">
                                                            {{ userRole === 'admin' ? 'Administrador' : 'Tesorero' }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <svg
                                                    class="-me-0.5 ms-3 h-4 w-4 text-gray-400 group-hover:text-green-500 transition-colors"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50/50">
                                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest">Mi Cuenta</p>
                                        </div>
                                        
                                        <DropdownLink :href="route('profile.edit')" class="flex items-center py-3">
                                            <span class="mr-3 text-lg">⚙️</span>
                                            <span class="font-bold">Editar Perfil</span>
                                        </DropdownLink>
                                        
                                        <div v-if="$page.props.auth.user.role === 'admin'" class="px-4 py-2 mt-2 border-t border-gray-100 border-b bg-gray-50/50">
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Administración</p>
                                        </div>

                                        <DropdownLink 
                                            v-if="$page.props.auth.user.role === 'admin'"
                                            :href="route('users')"
                                            class="flex items-center py-3 text-indigo-700"
                                        >
                                            <span class="mr-3 text-lg">👥</span>
                                            <span class="font-bold">Usuarios del Sistema</span>
                                        </DropdownLink>

                                        <DropdownLink 
                                            v-if="$page.props.auth.user.role === 'admin'"
                                            :href="route('nurses')"
                                            class="flex items-center py-3 text-purple-700"
                                        >
                                            <span class="mr-3 text-lg">👩‍⚕️</span>
                                            <span class="font-bold">Gestión de Personal</span>
                                        </DropdownLink>

                                        <div class="border-t border-gray-100 mt-2"></div>
                                        
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                            class="flex items-center py-3 text-red-600 hover:bg-red-50 w-full text-left"
                                        >
                                            <span class="mr-3 text-lg">🚪</span>
                                            <span class="font-bold">Cerrar Sesión</span>
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            🏠 Dashboard
                        </ResponsiveNavLink>
                        
                        <ResponsiveNavLink
                            v-if="userRole === 'admin'"
                            :href="route('reports')"
                            :active="route().current('reports')"
                        >
                            📊 Reportes
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="border-t border-gray-200 pb-1 pt-4">
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800">
                                {{ page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ page.props.auth.user.email }}
                            </div>
                            <div class="mt-1">
                                <span class="text-xs px-2 py-1 rounded-full" 
                                      :class="userRole === 'admin' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'">
                                    {{ userRole === 'admin' ? 'Administrador' : 'Tesorero' }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Perfil
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Cerrar Sesión
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-gradient-to-r from-green-50 to-blue-50 shadow"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
