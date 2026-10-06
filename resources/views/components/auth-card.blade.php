@props(['title', 'description'])

<section {{ $attributes->merge(['class' => 'w-full max-w-md rounded-3xl border border-white/10 bg-white/95 p-6 text-slate-900 shadow-2xl shadow-black/30 sm:p-8']) }}>
    <div class="flex flex-col gap-2">
        <span class="text-xs font-black uppercase tracking-[0.24em] text-denarius-600">Denarius EdTech</span>
        <h1 class="text-3xl font-black tracking-tight">{{ $title }}</h1>
        <p class="text-sm leading-6 text-slate-600">{{ $description }}</p>
    </div>

    <div class="mt-7">
        {{ $slot }}
    </div>
</section>
