    <script setup>
import LmsLayout from '@/Layouts/LmsLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    course: Object,
    syllabus: Array,
});

// Форматирование цены
const formatPrice = (price) => {
    if (price === 0) return 'Бесплатно';
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(price / 100);
};

// Функция для подсчета общего кол-ва уроков (просто для статистики)
const getTotalLessons = () => {
    let count = 0;
    props.syllabus.forEach(module => {
        count += module.lessons.length;
        if (module.children) {
            module.children.forEach(child => count += child.lessons.length);
        }
    });
    return count;
};

import { router, usePage } from '@inertiajs/vue3';

// Функция записи
const enroll = () => {
    router.post(route('courses.enroll', props.course.slug), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Можно добавить уведомление (Toast), но пока хватит стандартного поведения
        }
    });
};
</script>

<template>
    <Head :title="course.title" />

    <LmsLayout>
        <div class="relative bg-gray-900 text-white overflow-hidden">
            <div class="absolute inset-0 opacity-30">
                <img 
                    v-if="course.thumbnail_url" 
                    :src="'/storage/' + course.thumbnail_url" 
                    class="w-full h-full object-cover blur-sm scale-105"
                    alt=""
                >
                <div v-else class="w-full h-full bg-gradient-to-r from-indigo-900 to-purple-900"></div>
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/80 to-transparent"></div>

            <div class="relative max-w-5xl mx-auto px-6 py-16 md:py-24 flex flex-col md:flex-row gap-10 items-start">
                
                <div class="flex-1 space-y-6">
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 rounded-full text-xs font-bold uppercase tracking-wide">
                            Онлайн-курс
                        </span>
                        <span class="flex items-center text-gray-300 text-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ getTotalLessons() }} уроков
                        </span>
                    </div>

                    <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight leading-tight">
                        {{ course.title }}
                    </h1>

                    <div class="flex items-center gap-4 pt-2">
                        <img :src="course.teacher?.avatar_url || 'https://ui-avatars.com/api/?name=' + (course.teacher?.name || 'T')" class="w-10 h-10 rounded-full border border-gray-600" alt="">
                        <div>
                            <p class="text-sm text-gray-400">Автор курса</p>
                            <p class="text-white font-medium">{{ course.teacher?.name }}</p>
                        </div>
                    </div>
                </div>

                <div class="w-full md:w-80 bg-white/10 backdrop-blur-md border border-white/10 rounded-2xl p-6 shadow-2xl">
                    <div class="mb-6">
                        <p class="text-gray-300 text-sm mb-1">Стоимость обучения</p>
                        <p class="text-3xl font-bold text-white">
                            {{ formatPrice(course.price) }}
                        </p>
                    </div>

                    <button 
    @click="enroll"
    class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-4 px-6 rounded-xl transition shadow-lg shadow-indigo-900/50 flex items-center justify-center gap-2"
>
    <span>{{ course.price === 0 ? 'Начать бесплатно' : 'Купить курс' }}</span>
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
</button>
                    
                    <p class="text-xs text-center text-gray-400 mt-4">
                        Доступ навсегда • Сертификат
                    </p>
                </div>
            </div>
        </div>

        <div class="max-w-5xl mx-auto px-6 py-12 grid grid-cols-1 lg:grid-cols-3 gap-12">
            
            <div class="lg:col-span-2 space-y-12">
                
                <section>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">О чем этот курс</h3>
                    <div class="prose prose-indigo text-gray-600 leading-relaxed">
                        {{ course.description || 'Автор не добавил описание.' }}
                    </div>
                </section>

                <section>
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">Программа курса</h3>
                        <span class="text-sm text-gray-500">{{ syllabus.length }} модулей</span>
                    </div>

                    <div class="space-y-4">
                        <details 
                            v-for="(module, index) in syllabus" 
                            :key="module.id" 
                            class="group bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm open:ring-2 open:ring-indigo-100 transition-all"
                            :open="index === 0"
                        >
                            <summary class="flex items-center justify-between px-6 py-4 cursor-pointer bg-gray-50 hover:bg-gray-100 transition select-none">
                                <div class="flex items-center gap-4">
                                    <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-indigo-100 text-indigo-600 rounded-lg font-bold text-sm">
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800">{{ module.title }}</h4>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ module.lessons.length + (module.children?.reduce((acc, child) => acc + child.lessons.length, 0) || 0) }} уроков
                                        </p>
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </summary>
                            
                            <div class="border-t border-gray-100">
                                <div v-if="module.lessons.length > 0" class="divide-y divide-gray-100">
                                    <div v-for="lesson in module.lessons" :key="lesson.id" class="px-6 py-3 flex items-center justify-between hover:bg-gray-50 transition group/lesson cursor-pointer">
                                        <div class="flex items-center gap-3">
                                            <svg class="w-4 h-4 text-gray-400 group-hover/lesson:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span class="text-sm text-gray-700 group-hover/lesson:text-gray-900 font-medium">
                                                {{ lesson.title }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-400" v-if="lesson.duration_minutes > 0">{{ lesson.duration_minutes }} мин</span>
                                    </div>
                                </div>

                                <div v-if="module.children && module.children.length > 0" class="bg-gray-50/50 px-6 py-4 space-y-3">
                                    <div v-for="submodule in module.children" :key="submodule.id">
                                        <h5 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2 pl-2 border-l-2 border-indigo-200">
                                            {{ submodule.title }}
                                        </h5>
                                        <div class="bg-white rounded-lg border border-gray-100 divide-y divide-gray-100">
                                            <div v-for="subLesson in submodule.lessons" :key="subLesson.id" class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50 transition cursor-pointer">
                                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                <span class="text-sm text-gray-600">{{ subLesson.title }}</span>
                                            </div>
                                            <div v-if="submodule.lessons.length === 0" class="p-3 text-xs text-gray-400 italic">
                                                В этой теме пока нет уроков
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="module.lessons.length === 0 && (!module.children || module.children.length === 0)" class="px-6 py-4 text-sm text-gray-400 italic">
                                    Модуль пока пуст или находится в разработке.
                                </div>
                            </div>
                        </details>
                    </div>
                </section>

            </div>

            <div class="hidden lg:block space-y-8">
                <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
                    <h4 class="font-bold text-gray-900 mb-4">Что входит в курс</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3 text-sm text-gray-600">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Доступ к видео-лекциям навсегда
                        </li>
                        <li class="flex items-start gap-3 text-sm text-gray-600">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Практические домашние задания
                        </li>
                        <li class="flex items-start gap-3 text-sm text-gray-600">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Проверка работ куратором
                        </li>
                        <li class="flex items-start gap-3 text-sm text-gray-600">
                            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Сертификат об окончании
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </LmsLayout>
</template>