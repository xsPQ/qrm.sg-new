@php
    // Fallback renderer (P2-T02): shows every content key/value as a
    // definition list. Each value is HTML-escaped through Blade's {{ }},
    // so payload data renders XSS-safe for any (including future) type.
    $fields = [];

    if (is_array($content)) {
        foreach ($content as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $fields[ucfirst((string) $key)] = (string) $value;
        }
    }

    if (empty($fields)) {
        $fields[__('Content')] = '';
    }
@endphp

@include('qr-codes.partials._fields', ['fields' => $fields])
