<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

// Состояние меню (для мобильных)
const showingNavigationDropdown = ref(false);
const user = usePage().props.auth.user;

// Ссылки навигации
const navLinks = [
    { name: 'Каталог курсов', route: 'courses.index', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
    { name: 'Мое обучение', route: 'dashboard', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' }, // Пока ведет на dashboard
    { name: 'Профиль', route: 'profile.edit', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
];
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        
        <aside class="hidden md:flex flex-col w-64 bg-white border-r border-gray-100 fixed h-full z-20">
            <div class="h-16 flex items-center px-8 border-b border-gray-50">
                <Link :href="route('courses.index')" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-bold text-lg">L</div>
                    <span class="text-xl font-bold text-gray-800 tracking-tight">LMS Core</span>
                </Link>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2">
                <Link 
                    v-for="link in navLinks" 
                    :key="link.name"
                    :href="route(link.route)"
                    :class="[
                        route().current(link.route) 
                            ? 'bg-indigo-50 text-indigo-700' 
                            : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900',
                        'group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200'
                    ]"
                >
                    <svg 
                        class="mr-3 flex-shrink-0 h-6 w-6 transition-colors duration-200" 
                        :class="route().current(link.route) ? 'text-indigo-600' : 'text-gray-400 group-hover:text-gray-600'"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" :d="link.icon" />
                    </svg>
                    {{ link.name }}
                </Link>
            </nav>

            <div class="p-4 border-t border-gray-50">
                <div class="flex items-center gap-3">
                    <img 
    :src="user.avatar_url ? '/storage/' + user.avatar_url : 'https://ui-avatars.com/api/?name=' + user.name + '&background=random'" 
    class="w-10 h-10 rounded-full border-2 border-white shadow-sm object-cover" 
    alt="Avatar"
>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ user.name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ user.email }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <main class="flex-1 md:ml-64 relative">
            <slot />
        </main>
    </div>
</template>