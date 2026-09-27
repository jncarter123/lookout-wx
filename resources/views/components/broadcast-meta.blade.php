{{--
    Where the browser connects for live updates, read by resources/js/config/pusher.js.
    Rendered from config at request time so one built image works with any Pusher app
    or Reverb server. Only public values: the app key is visible to every browser that
    connects anyway; the secret never leaves the server.
--}}
@php($driver = config('broadcasting.default'))
<meta name="broadcast-driver" content="{{ $driver }}">
@if ($driver === 'reverb')
    <meta name="reverb-key" content="{{ config('broadcasting.connections.reverb.key') }}">
    <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host') }}">
    <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.options.port') }}">
    <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.options.scheme') }}">
@elseif ($driver === 'pusher')
    <meta name="pusher-key" content="{{ config('broadcasting.connections.pusher.key') }}">
    <meta name="pusher-cluster" content="{{ config('broadcasting.connections.pusher.options.cluster') }}">
@endif
