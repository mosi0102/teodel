@php
    $c = $contacts ?? \App\Support\SiteContent::contacts();
    $home = url('/');
@endphp
<footer class="st-foot" id="contact">
    <div class="st-wrap">
        <div class="st-foot__grid">
            <div class="st-foot__about">
                <a href="{{ $home }}" class="st-logo" aria-label="{{ __('site.home_link') }}">          <img src="{{ asset('images/logo.png') }}" alt="{{ __('site.brand') }}" width="104" height="40" style="height: 40px" loading="lazy"></a>
                <h2 class="st-foot__t">{{ __('site.footer.about_t') }}</h2>
                <p>{{ __('site.footer.about') }}</p>
            </div>

            <nav aria-label="{{ __('site.footer.links_t') }}">
                <h2 class="st-foot__t">{{ __('site.footer.links_t') }}</h2>
                <ul class="st-foot__ls">
                    <li><a href="{{ url('/faq') }}">{{ __('site.footer.faq') }}</a></li>
                    <li><a href="{{ url('/terms') }}">{{ __('site.footer.terms') }}</a></li>
                    <li><a href="{{ url('/support') }}">{{ __('site.nav.complaint') }}</a></li>
                </ul>
            </nav>

            <nav aria-label="{{ __('site.footer.services_t') }}">
                <h2 class="st-foot__t">{{ __('site.footer.services_t') }}</h2>
                <ul class="st-foot__ls">
                    <li><a href="{{ url('/music') }}">{{ __('site.footer.music') }}</a></li>
                    <li><a href="{{ url('/level-test') }}">{{ __('site.footer.level') }}</a></li>
                    <li><a href="{{ url('/dictionary') }}">{{ __('site.footer.dictionary') }}</a></li>
                </ul>
            </nav>

            <div>
                <h2 class="st-foot__t">{{ __('site.footer.contact_t') }}</h2>
                <ul class="st-foot__ls st-foot__ct">
                    <li><x-icon name="mail" class="size-4" /><a href="mailto:{{ $c['email'] }}" dir="ltr">{{ $c['email'] }}</a></li>
                    <li><x-icon name="send" class="size-4" /><a href="{{ $c['telegram_url'] }}" rel="noopener" target="_blank" dir="ltr">{{ $c['telegram'] }}</a></li>
                    <li><x-icon name="instagram" class="size-4" /><a href="{{ $c['instagram_url'] }}" rel="noopener" target="_blank" dir="ltr">{{ $c['instagram'] }}</a></li>
                </ul>
            </div>
        </div>
        <p class="st-foot__copy">{{ __('site.footer.rights') }}</p>
    </div>
</footer>
