{{-- Runs before first paint to avoid a light/dark flash. The only inline script; allowed by the CSP nonce. --}}
<script nonce="{{ $cspNonce ?? '' }}">try{var t=localStorage.getItem('theme');if(t==='dark'||t==='light')document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
