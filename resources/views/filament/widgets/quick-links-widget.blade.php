<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-squares-2x2"
                    class="h-5 w-5 text-indigo-500"
                />
                <span>Быстрый доступ к разделам</span>
            </div>
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mt-2">
            @foreach($links as $link)
                <a href="{{ $link['url'] }}" class="relative group flex items-start p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm transition duration-300 hover:shadow-md hover:border-indigo-500 dark:hover:border-indigo-400">
                    <div class="rounded-xl transition duration-300 group-hover:scale-110" style="flex-shrink: 0; background-color: rgba(99, 102, 241, 0.1); color: rgb(99, 102, 241); width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                        <x-filament::icon :icon="$link['icon']" class="w-6 h-6" />
                    </div>
                    <div class="flex-grow" style="margin-left: 16px; padding-top: 2px;">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                            {{ $link['title'] }}
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2 leading-relaxed">
                            {{ $link['description'] }}
                        </p>
                    </div>
                    <div class="text-gray-300 dark:text-gray-700 group-hover:text-indigo-500 transition" style="position: absolute; right: 16px; top: 16px;">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
