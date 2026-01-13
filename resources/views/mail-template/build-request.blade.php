Dear {{ $details['customer_name'] }},

<p style="color: black; text-align: justify">
    Thank you for submitting your request to build your
    @if($details['is_android'] && $details['is_ios'])
        Android & iOS
    @elseif($details['is_android'])
        Android
    @elseif($details['is_ios'])
        iOS
    @endif
    app, the development of <b>{{ $details['app_name'] ?: 'the app' }}</b>. The app build process is now in progress and we are excited to bring your app to life!
</p>

<p style="color: black; text-align: justify">
    Our fully automated build system is now working diligently to ensure that every detail of the app is aligned with your specifications and meets our high-quality standards. The build process will take approximately 15-30 minutes. You will receive a confirmation email after the process completes.
</p>

<p style="color: black; text-align: justify">
    Thank you for choosing Appza.
</p>

<p style="color: black">
    Best regards,<br>
    <strong>Appza Team</strong><br><br>

    Web:
    <a href="https://www.lazycoders.co" target="_blank" style="color: black; text-decoration: underline;">
        www.lazycoders.co
    </a><br>

    Support Portal:
    <a href="https://tinyurl.com/LazyCodersPortal" target="_blank" style="color: black; text-decoration: underline;">
        https://tinyurl.com/LazyCodersPortal
    </a><br>

    Email:support@lazycoders.co <br>
    Phone: +1 872 282 4343
</p>

