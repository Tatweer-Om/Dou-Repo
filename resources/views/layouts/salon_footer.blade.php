<!-- Mobile Bottom Nav -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-surface-container-lowest/80 backdrop-blur-xl flex justify-around items-center py-3 border-t border-outline-variant/10">
<a class="flex flex-col items-center gap-1 text-on-surface-variant" href="#">
<span class="material-symbols-outlined text-xl">dashboard</span>
<span class="text-[10px] font-bold uppercase tracking-tighter">Home</span>
</a>
<a class="flex flex-col items-center gap-1 text-primary" href="{{ route('booking_management') }}">
<span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">calendar_month</span>
<span class="text-[10px] font-bold uppercase tracking-tighter">Bookings</span>
</a>
<a class="flex flex-col items-center gap-1 text-on-surface-variant" href="#">
<span class="material-symbols-outlined text-xl">person</span>
<span class="text-[10px] font-bold uppercase tracking-tighter">Clients</span>
</a>
<a class="flex flex-col items-center gap-1 text-on-surface-variant" href="#">
<span class="material-symbols-outlined text-xl">payments</span>
<span class="text-[10px] font-bold uppercase tracking-tighter">Finance</span>
</a>
</nav>
<!-- Bootstrap JS bundle (includes Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

         <script src="{{asset('js/custom.js')}}"></script>
         <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.20/dist/sweetalert2.all.min.js"></script>
<script>
function logout() {
    Swal.fire({
        title: '{{ trans('messages.confirm_logout', [], session('locale')) }}',
        text: '{{ trans('messages.are_you_sure_logout', [], session('locale')) }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '{{ trans('messages.logout_title', [], session('locale')) }}',
        cancelButtonText: '{{ trans('messages.cancel', [], session('locale')) }}'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('logout') }}';
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
@include('custom_js.custom_js')


</body></html>