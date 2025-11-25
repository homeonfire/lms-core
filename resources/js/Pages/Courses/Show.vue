<script setup>
import LmsLayout from '@/Layouts/LmsLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    course: Object,
    syllabus: Array,
    userOrder: Object, // <--- Принимаем заказ (может быть null)
});

// Форматирование цены
const formatPrice = (price) => {
    if (price === 0) return 'Бесплатно';
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(price);
};

// Умный вывод цены в заголовке
const getDisplayPrice = () => {
    // Если есть тарифы
    if (props.course.tariffs && props.course.tariffs.length > 0) {
        return 'от ' + formatPrice(props.course.tariffs[0].price);
    }
    // Если тарифов нет
    return formatPrice(props.course.price);
};

// Функция покупки / выбора тарифа
const enroll = (tariffId = null) => {
    router.post(route('courses.enroll', props.course.slug), {
        tariff_id: tariffId
    }, { preserveScroll: true });
};

// Подсчет общего количества уроков для статистики
const getTotalLessons = () => {
    let count = 0;
    props.syllabus.forEach(m => {
        count += m.lessons.length;
        if (m.children) m.children.forEach(c => count += c.lessons.length);
    });
    return count;
};
</script>

<template>
    <Head :title="course.title" />

    <LmsLayout>
        <!-- Hero Section -->
        <div class="relative bg-gray-900 text-white overflow-hidden">
            <!-- Фон -->
            <div class="absolute inset-0 opacity-30">
                <img v-if="course.thumbnail_url" :src="'/storage/' + course.thumbnail_url" class="w-full h-full object-cover blur-sm scale-105">
                <div v-else class="w-full h-full bg-gradient-to-r from-indigo-900 to-purple-900"></div>
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/80 to-transparent"></div>

            <div class="relative max-w-5xl mx-auto px-6 py-16 md:py-24 flex flex-col md:flex-row gap-10 items-start">
                <!-- Левая часть: Инфо -->
                <div class="flex-1 space-y-6">
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 rounded-full text-xs font-bold uppercase tracking-wide">Онлайн-курс</span>
                        <span class="flex items-center text-gray-300 text-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ getTotalLessons() }} уроков
                        </span>
                    </div>

                    <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight leading-tight">{{ course.title }}</h1>

                    <div class="flex items-center gap-4 pt-2">
                        <img 
                            :src="course.teacher?.avatar_url ? '/storage/' + course.teacher.avatar_url : 'https://ui-avatars.com/api/?name=' + (course.teacher?.name || 'T')" 
                            class="w-10 h-10 rounded-full border border-gray-600 object-cover"
                        >
                        <div>
                            <p class="text-sm text-gray-400">Автор курса</p>
                            <p class="text-white font-medium">{{ course.teacher?.name }}</p>
                        </div>
                    </div>
                </div>

                <!-- ПРАВАЯ КОЛОНКА: Действие -->
                <div class="w-full md:w-96 space-y-6">
                    
                    <!-- ВАРИАНТ 1: УЖЕ КУПЛЕНО -->
                    <div v-if="userOrder" class="bg-green-900/80 backdrop-blur-md border border-green-500/50 rounded-2xl p-6 shadow-2xl">
                        <div class="mb-6 text-center">
                            <div class="w-16 h-16 bg-green-500 text-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-1">Доступ открыт</h3>
                            <p class="text-green-200 text-sm">
                                Ваш тариф: <span class="font-bold text-white">{{ userOrder.tariff ? userOrder.tariff.name : 'Стандарт' }}</span>
                            </p>
                        </div>

                        <Link 
                            :href="route('learning.lesson', course.slug)" 
                            class="block w-full bg-white text-green-700 hover:bg-green-50 font-bold py-3 px-6 rounded-xl text-center transition shadow-lg"
                        >
                            К обучению →
                        </Link>
                    </div>

                    <!-- ВАРИАНТ 2: ЕЩЕ НЕ КУПЛЕНО -->
                    <div v-else class="space-y-6">
                        
                        <!-- Цена -->
                        <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-2xl p-6 shadow-xl">
                            <p class="text-gray-300 text-sm mb-1">Стоимость обучения</p>
                            <p class="text-3xl font-bold text-white">
                                {{ getDisplayPrice() }}
                            </p>
                        </div>

                        <!-- Список тарифов (если есть) -->
                        <div v-if="course.tariffs && course.tariffs.length > 0" class="bg-white/10 backdrop-blur-md border border-white/20 rounded-xl p-4">
                            <h3 class="font-bold text-white text-lg mb-4">Выберите тариф</h3>
                            <div class="space-y-3">
                                <div 
                                    v-for="tariff in course.tariffs" :key="tariff.id"
                                    class="bg-gray-900/50 border border-indigo-500/30 rounded-lg p-4 hover:border-indigo-400 transition group cursor-pointer"
                                    @click="enroll(tariff.id)"
                                >
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-bold text-indigo-200 group-hover:text-white transition">{{ tariff.name }}</span>
                                        <span class="font-bold text-white">{{ formatPrice(tariff.price) }}</span>
                                    </div>
                                    <button class="w-full py-2 text-sm font-bold rounded bg-indigo-600 hover:bg-indigo-500 text-white transition">
                                        Выбрать
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Кнопка покупки (если нет тарифов) -->
                        <button 
                            v-else 
                            @click="enroll(null)" 
                            class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-4 px-6 rounded-xl transition shadow-lg shadow-indigo-900/50 flex items-center justify-center gap-2"
                        >
                            <span>{{ course.price === 0 ? 'Начать бесплатно' : 'Купить курс' }}</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-4 4m4-4H6"></path></svg>
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- MAIN CONTENT (Программа) -->
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
                                <div class="flex items-center gap-4 overflow-hidden">
                                    <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-indigo-100 text-indigo-600 rounded-lg font-bold text-sm">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="flex flex-col">
                                        <h4 class="font-bold text-gray-800">{{ module.title }}</h4>
                                        
                                        <!-- ТАРИФЫ МОДУЛЯ -->
                                        <div v-if="module.tariffs && module.tariffs.length > 0" class="flex flex-wrap gap-1 mt-1">
                                            <span v-for="t in module.tariffs" :key="t.id" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-800">
                                                {{ t.name }}
                                            </span>
                                        </div>
                                        <p v-else class="text-xs text-gray-500 mt-0.5">
                                            {{ module.lessons.length + (module.children?.reduce((acc, child) => acc + child.lessons.length, 0) || 0) }} уроков
                                        </p>
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-open:rotate-180 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </summary>
                            
                            <div class="border-t border-gray-100">
                                <div v-if="module.lessons.length > 0" class="divide-y divide-gray-100">
                                    <div v-for="lesson in module.lessons" :key="lesson.id" class="px-6 py-3 flex items-center justify-between hover:bg-gray-50 transition group/lesson">
                                        <div class="flex items-center gap-3 overflow-hidden">
                                            <svg class="w-4 h-4 text-gray-400 group-hover/lesson:text-indigo-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <div class="flex flex-col">
                                                <span class="text-sm text-gray-700 group-hover/lesson:text-gray-900 font-medium">{{ lesson.title }}</span>
                                                <!-- ТАРИФЫ УРОКА -->
                                                <div v-if="lesson.tariffs && lesson.tariffs.length > 0" class="flex flex-wrap gap-1 mt-0.5">
                                                    <span v-for="t in lesson.tariffs" :key="t.id" class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                                        {{ t.name }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="text-xs text-gray-400 flex-shrink-0 ml-2" v-if="lesson.duration_minutes > 0">{{ lesson.duration_minutes }} мин</span>
                                    </div>
                                </div>

                                <div v-if="module.children && module.children.length > 0" class="bg-gray-50/50 px-6 py-4 space-y-3">
                                    <div v-for="submodule in module.children" :key="submodule.id">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h5 class="text-xs font-bold uppercase tracking-wider text-gray-400 pl-2 border-l-2 border-indigo-200">{{ submodule.title }}</h5>
                                            <div v-if="submodule.tariffs && submodule.tariffs.length > 0" class="flex flex-wrap gap-1">
                                                <span v-for="t in submodule.tariffs" :key="t.id" class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium bg-purple-50 text-purple-700 border border-purple-100">
                                                    {{ t.name }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="bg-white rounded-lg border border-gray-100 divide-y divide-gray-100">
                                            <div v-for="subLesson in submodule.lessons" :key="subLesson.id" class="px-4 py-3 flex items-center gap-3 hover:bg-gray-50 transition">
                                                <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                <div class="flex flex-col">
                                                    <span class="text-sm text-gray-600">{{ subLesson.title }}</span>
                                                    <div v-if="subLesson.tariffs && subLesson.tariffs.length > 0" class="flex flex-wrap gap-1 mt-0.5">
                                                        <span v-for="t in subLesson.tariffs" :key="t.id" class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                                            {{ t.name }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>
                </section>
            </div>

            <!-- Правая колонка: Преимущества -->
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