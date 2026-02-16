<x-mail::message>
# Welcome, {{ $user->name }}!

You have successfully created your account on **{{ config('app.name') }}**. Here are your login details:

- **Email:** {{ $user->email }}
- **Password:** {{ $password }}

<x-mail::button :url="$loginUrl">
Login to your account
</x-mail::button>

Please keep your password safe. You can change it after logging in from your profile settings.

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
