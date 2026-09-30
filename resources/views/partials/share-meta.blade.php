@php
    $shareTitle = config('app.name');
    $shareDescription = 'Put the work on record';
    $shareImage = url('/share.jpg');
@endphp
<meta name="description" content="{{ $shareDescription }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $shareTitle }}">
<meta property="og:title" content="{{ $shareTitle }}">
<meta property="og:description" content="{{ $shareDescription }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $shareImage }}">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1024">
<meta property="og:image:height" content="682">
<meta property="og:image:alt" content="A kettlebell and a loaded barbell on the gym floor">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $shareTitle }}">
<meta name="twitter:description" content="{{ $shareDescription }}">
<meta name="twitter:image" content="{{ $shareImage }}">
