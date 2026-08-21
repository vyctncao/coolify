{{ Illuminate\Mail\Markdown::parse('---') }}

Thank you,<br>
{{ config('app.name') ?: config('branding.name') }}

{{ Illuminate\Mail\Markdown::parse('[Contact Support](' . config('branding.urls.contact') . ')') }}
