@php
    $address = App\Support\Locale::isFarsi() && filled($contact['address_fa']) ? $contact['address_fa'] : $contact['address'];
    $hasDetails = filled($contact['email']) || filled($contact['phone']) || filled($address) || filled($contact['instagram']);
@endphp

<x-layouts::site :title="__('Contact')" :description="__('Questions about a competition, ideas for a collaboration or press enquiries — send us a message.')">
    <section class="pt-36 pb-16 lg:pt-52 lg:pb-28">
        <div class="container-site">
            <div class="grid gap-14 lg:mx-[8.333%] lg:grid-cols-12 lg:gap-12">
                {{-- Intro and details --}}
                <div class="lg:col-span-5">
                    <div data-fade>
                        <h1 class="display-1 flex flex-col">
                            <span>{{ __('Contact') }}</span>
                            <em class="text-bronze">{{ __('We would love to hear from you') }}</em>
                        </h1>
                        <p class="mt-6 max-w-md text-ink/80">
                            {{ __('Questions about a competition, ideas for a collaboration or press enquiries — send us a message and we will reply as soon as we can.') }}
                        </p>
                    </div>

                    @if ($hasDetails)
                        <dl class="mt-10 space-y-5 text-[16px] leading-7" data-fade>
                            @if (filled($contact['email']))
                                <div><dt class="text-[14px] text-ink/55">{{ __('Email') }}</dt><dd><a href="mailto:{{ $contact['email'] }}" class="hover:text-gold" dir="ltr">{{ $contact['email'] }}</a></dd></div>
                            @endif
                            @if (filled($contact['phone']))
                                <div><dt class="text-[14px] text-ink/55">{{ __('Phone') }}</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['phone']) }}" class="hover:text-gold" dir="ltr">{{ $contact['phone'] }}</a></dd></div>
                            @endif
                            @if (filled($address))
                                <div><dt class="text-[14px] text-ink/55">{{ __('Address') }}</dt><dd class="whitespace-pre-line">{{ $address }}</dd></div>
                            @endif
                            @if (filled($contact['instagram']))
                                <div><dt class="text-[14px] text-ink/55">Instagram</dt><dd><a href="https://instagram.com/{{ ltrim($contact['instagram'], '@') }}" class="hover:text-gold" dir="ltr" rel="noopener" target="_blank">{{ '@'.ltrim($contact['instagram'], '@') }}</a></dd></div>
                            @endif
                        </dl>
                    @endif

                    @if ($image)
                        <div class="mt-12 hidden lg:block" data-reveal-parent>
                            <div class="relative aspect-[4/3] overflow-hidden rounded bg-white" data-reveal-item>
                                <img src="{{ $image }}" alt="" class="absolute inset-0 size-full object-cover" loading="lazy">
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Form --}}
                <div class="scroll-mt-24 lg:col-span-6 lg:col-start-7 lg:scroll-mt-28" id="contact-form">
                    <div class="rounded bg-white p-7 sm:p-10 lg:p-12" data-fade="current">
                        @if (session('contact_sent'))
                            <div role="status" class="py-8 text-center">
                                <div class="mx-auto flex size-14 items-center justify-center rounded-full border border-gold text-gold">
                                    <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                                </div>
                                <h2 class="display-3 mt-6">{{ __('Thank you!') }}</h2>
                                <p class="mt-2 text-ink/75">{{ __('Your message has been sent. We will get back to you soon.') }}</p>
                                <a href="{{ route('contact') }}#contact-form" class="btn btn-dark mt-8">{{ __('Send another message') }}<x-site.arrow /></a>
                            </div>
                        @else
                            <h2 class="display-3">{{ __('Send us a message') }}</h2>

                            <form method="POST" action="{{ route('contact.store') }}" class="mt-8 space-y-7" novalidate>
                                @csrf

                                <div>
                                    <label for="contact-name" class="field-label">{{ __('Your name') }}</label>
                                    <input id="contact-name" name="name" type="text" value="{{ old('name') }}" required maxlength="120" autocomplete="name"
                                        class="field-input" @error('name') aria-invalid="true" aria-describedby="contact-name-error" @enderror>
                                    @error('name') <p id="contact-name-error" class="field-error">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="contact-email" class="field-label">{{ __('Email address') }}</label>
                                    <input id="contact-email" name="email" type="email" value="{{ old('email') }}" required maxlength="190" autocomplete="email" dir="ltr"
                                        class="field-input rtl:text-end" @error('email') aria-invalid="true" aria-describedby="contact-email-error" @enderror>
                                    @error('email') <p id="contact-email-error" class="field-error">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="contact-topic" class="field-label">{{ __('Subject') }}</label>
                                    <select id="contact-topic" name="topic" class="field-input field-select">
                                        @foreach (App\Models\ContactMessage::TOPICS as $key => $label)
                                            <option value="{{ $key }}" @selected(old('topic', 'general') === $key)>{{ __($label) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="contact-message" class="field-label">{{ __('Message') }}</label>
                                    <textarea id="contact-message" name="message" rows="6" required maxlength="5000"
                                        class="field-input resize-y" @error('message') aria-invalid="true" aria-describedby="contact-message-error" @enderror>{{ old('message') }}</textarea>
                                    @error('message') <p id="contact-message-error" class="field-error">{{ $message }}</p> @enderror
                                </div>

                                {{-- Spam trap: people never see or fill this field. --}}
                                <div class="hidden" aria-hidden="true">
                                    <label for="contact-website">{{ __('Leave this field empty') }}</label>
                                    <input id="contact-website" name="website" type="text" tabindex="-1" autocomplete="off">
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-6 pt-2">
                                    <p class="max-w-xs text-[13px] leading-6 text-ink/55">{{ __('We only use your details to answer your message.') }}</p>
                                    <button type="submit" class="btn btn-dark cursor-pointer" data-test="contact-submit">{{ __('Send message') }}<x-site.arrow /></button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts::site>
