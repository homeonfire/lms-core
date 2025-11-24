<script setup>
import LmsLayout from '@/Layouts/LmsLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ courses: Array });
</script>

<template>
    <Head title="Мое обучение" />

    <LmsLayout>
        <div class="py-12 px-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Мое обучение</h1>

            <div v-if="courses.length === 0" class="text-center py-20 bg-white rounded-2xl border border-gray-100">
                <div class="text-6xl mb-4">🎓</div>
                <h3 class="text-xl font-bold text-gray-700 mb-2">Вы пока нигде не учитесь</h3>
                <p class="text-gray-500 mb-6">Самое время выбрать новый навык.</p>
                <Link :href="route('courses.index')" class="text-indigo-600 font-bold hover:underline">Перейти в каталог</Link>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="course in courses" :key="course.id" class="bg-white border border-gray-200 rounded-xl p-6 flex flex-col hover:shadow-lg transition">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
                             <img v-if="course.thumbnail_url" :src="'/storage/' + course.thumbnail_url" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 line-clamp-1">{{ course.title }}</h3>
                            <p class="text-xs text-gray-500">{{ course.teacher?.name }}</p>
                        </div>
                    </div>
                    
                    <div class="w-full bg-gray-100 rounded-full h-2 mb-4 overflow-hidden">
                        <div 
                            class="bg-green-500 h-2 rounded-full transition-all duration-1000 ease-out" 
                            :style="{ width: course.progress + '%' }"
                        ></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mb-6">
                        <span>Прогресс</span>
                        <span class="font-bold text-gray-900">{{ course.progress }}%</span>
                    </div>

                    <Link 
                        :href="route('learning.lesson', course.slug)" 
                        class="mt-auto w-full text-center bg-gray-900 text-white py-2 rounded-lg font-medium hover:bg-gray-800 transition"
                    >
                        Продолжить
                    </Link>
                </div>
            </div>
        </div>
    </LmsLayout>
</template>