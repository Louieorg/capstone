<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  const scrollPositionKey = `scroll-position:${window.location.pathname}${window.location.search}`;
  const scrollContainer = document.querySelector('.app-main > main');
  const savedScrollPosition = sessionStorage.getItem(scrollPositionKey);

  if (savedScrollPosition !== null) {
    const scrollPosition = Number(savedScrollPosition);

    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        window.scrollTo({ top: scrollPosition });

        if (scrollContainer) {
          scrollContainer.scrollTo({ top: scrollPosition });
        }
      });
    });

    sessionStorage.removeItem(scrollPositionKey);
  }

  document.addEventListener('submit', () => {
    const scrollPosition = scrollContainer ? scrollContainer.scrollTop : window.scrollY;

    sessionStorage.setItem(scrollPositionKey, String(scrollPosition));
  });

  

  // Initialize theme on page load
  if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }

  @if(session('success'))
  Swal.fire({
    toast: true, position: 'top-end', icon: 'success',
    title: "{{ session('success') }}",
    showConfirmButton: false, timer: 3000, timerProgressBar: true,
    background: '#13141a', color: '#f0f0f5',
    iconColor: '#fbb034',
  });
  @endif

  @if(session('info'))
  Swal.fire({
    toast: true, position: 'top-end', icon: 'info',
    title: "{{ session('info') }}",
    showConfirmButton: false, timer: 3500, timerProgressBar: true,
    background: '#13141a', color: '#f0f0f5',
    iconColor: '#60a5fa',
  });
  @endif

  @if(session('showLogin'))
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('[x-data]').__x.$data.loginOpen = true;
  });
  @endif

  function confirmLogout() {
    Swal.fire({
      title: 'Logging out?',
      text: 'You will be signed out of LIKHA.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#fbb034',
      cancelButtonColor: '#3e4055',
      confirmButtonText: 'Yes, sign out',
      background: '#13141a',
      color: '#f0f0f5',
    }).then(r => { if (r.isConfirmed) document.getElementById('logoutForm').submit(); });
  }
</script>
<script>
  if (window.lucide) {
    window.lucide.createIcons();
  }
</script>
@stack('scripts')
