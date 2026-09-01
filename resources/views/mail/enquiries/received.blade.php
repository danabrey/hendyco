<x-mail::message>
# New enquiry from {{ $enquiry->name }}

**Email:** {{ $enquiry->email }}

@if ($enquiry->phone)
**Phone:** {{ $enquiry->phone }}
@endif

**Sent from:** {{ $enquiry->source_page ? ucfirst($enquiry->source_page).' page' : 'Website' }}

**Message:**

{{ $enquiry->message }}

<x-mail::button :url="'mailto:'.$enquiry->email">
Reply to {{ $enquiry->name }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
