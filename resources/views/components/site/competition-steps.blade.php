{{-- The four steps of taking part in a competition, on the home and About pages. --}}
@php
    $steps = [
        ['title' => __('Read the brief'), 'text' => __('Each competition publishes a clear brief, the documents to submit, the prizes and the timeline.')],
        ['title' => __('Register and prepare'), 'text' => __('Create a free account, start your entry and develop your proposal at your own pace.')],
        ['title' => __('Upload before the deadline'), 'text' => __('Upload the required documents and submit. You can replace files until the deadline.')],
        ['title' => __('Independent jury'), 'text' => __('An independent jury reviews every entry — anonymously by default — and the results are announced.')],
    ];
@endphp

<ol {{ $attributes->class('grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-8') }}>
    @foreach ($steps as $i => $step)
        <li class="border-t border-ink/15 pt-6">
            <span class="font-serif text-[2.75rem] leading-none text-gold" aria-hidden="true">{{ App\Support\Locale::digits(sprintf('%02d', $i + 1)) }}</span>
            <h3 class="mt-5 text-[19px] leading-7 font-normal">{{ $step['title'] }}</h3>
            <p class="mt-2 text-[15px] leading-7 text-ink/75">{{ $step['text'] }}</p>
        </li>
    @endforeach
</ol>
