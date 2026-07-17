<x-filament-panels::page>
    <div style="display: flex; gap: 1.5rem;">
        <div style="flex: 1; min-width: 0;">
            <article class="prose dark:prose-invert max-w-none">
                {!! $content !!}
            </article>
        </div>

        <div style="width: 18rem; flex-shrink: 0;">
            <div class="flex items-center justify-between mb-2 px-1">
                <span class="text-base font-bold text-gray-800 dark:text-gray-200">Modules</span>
                <button
                    wire:click="copyMarkdown"
                    title="Copy Markdown"
                    class="text-gray-400 hover:text-primary-600 transition p-0.5"
                >
                    <x-heroicon-o-clipboard class="w-4 h-4" />
                </button>
            </div>
            <x-filament::section>

                <ul class="space-y-0.5 -mx-3 -mb-3">
                    @foreach ($modules as $key => $label)
                        <li>
                            <button
                                wire:click="selectModule('{{ $key }}')"
                                @class([
                                    'w-full text-left px-3 py-2 text-sm transition rounded-lg',
                                    'bg-primary-50 dark:bg-primary-900/20 text-primary-600 font-medium' => $selectedModule === $key,
                                    'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' => $selectedModule !== $key,
                                ])
                            >
                                {{ $label }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
