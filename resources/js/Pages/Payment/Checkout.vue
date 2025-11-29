<script setup>
import LmsLayout from '@/Layouts/LmsLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    order: Object,
    methods: Object,
});

const form = useForm({
    method: 'yookassa', // По умолчанию
});

const submit = () => {
    form.post(route('payment.pay', props.order.id));
};

const formatPrice = (val) => {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(val);
};
</script>

<template>
    <Head title="Оплата заказа" />

    <LmsLayout>
        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12 -mt-16">
            
            <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                
                <!-- Шапка заказа -->
                <div class="bg-gray-50 px-6 py-8 text-center border-b border-gray-100">
                    <h2 class="text-gray-500 text-sm font-medium uppercase tracking-wider mb-2">Сумма к оплате</h2>
                    <p class="text-4xl font-extrabold text-gray-900">{{ formatPrice(order.amount) }}</p>
                    <p class="mt-2 text-gray-600 text-sm">
                        Заказ #{{ order.id }} • {{ order.course.title }}
                    </p>
                    <div v-if="order.tariff" class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                        Тариф: {{ order.tariff.name }}
                    </div>
                </div>

                <!-- Выбор метода -->
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Выберите способ оплаты</label>
                        
                        <div class="space-y-3">
                            <!-- ЮKassa -->
                            <div v-if="methods.yookassa" 
                                class="relative flex items-center p-4 border rounded-xl cursor-pointer transition-all"
                                :class="form.method === 'yookassa' ? 'border-indigo-600 ring-1 ring-indigo-600 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'"
                                @click="form.method = 'yookassa'"
                            >
                                <input type="radio" name="method" value="yookassa" v-model="form.method" class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                <div class="ml-3 flex-1 flex items-center justify-between">
                                    <span class="block text-sm font-medium text-gray-900">ЮKassa (Карты РФ, SBP)</span>
                                    <!-- Логотип ЮКассы (условно) -->
                                    <div class="flex gap-1">
                                        <div class="w-8 h-5 bg-green-500 rounded"></div>
                                        <div class="w-8 h-5 bg-blue-500 rounded"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Заглушка для других методов -->
                            <div v-else class="text-center text-gray-500 text-sm py-4">
                                Нет доступных методов оплаты. Обратитесь в поддержку.
                            </div>
                        </div>
                    </div>

                    <!-- Кнопка -->
                    <button 
                        type="submit" 
                        :disabled="form.processing || !methods.yookassa"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition disabled:opacity-50"
                    >
                        <span v-if="form.processing">Переход к оплате...</span>
                        <span v-else>Оплатить {{ formatPrice(order.amount) }}</span>
                    </button>
                    
                    <p class="text-xs text-center text-gray-400 mt-4">
                        Безопасная оплата через шлюз провайдера. Мы не храним данные ваших карт.
                    </p>
                </form>
            </div>
            
            <div class="mt-6">
                <a :href="route('courses.show', order.course.slug)" class="text-sm text-gray-500 hover:text-gray-900">
                    ← Вернуться к описанию курса
                </a>
            </div>

        </div>
    </LmsLayout>
</template>