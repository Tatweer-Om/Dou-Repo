<script>
    $(document).ready(function() {
        // Function to insert variable into textarea
        window.insertVariable = function(variable) {
            const textarea = document.getElementById('sms_text');
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const before = text.substring(0, start);
            const after = text.substring(end, text.length);
            
            textarea.value = before + variable + after;
            textarea.focus();
            textarea.setSelectionRange(start + variable.length, start + variable.length);
            
            // Update preview
            updatePreview();
        };

        // Update preview
        function updatePreview() {
            const message = $('#sms_text').val();
            if (message) {
                $('#message_preview').text(message);
            } else {
                $('#message_preview').text('{{ trans('messages.select_message_type', [], session('locale')) }}');
            }
        }

        // Update preview on text change
        $('#sms_text').on('input', function() {
            updatePreview();
        });

        // Load existing SMS when message type is selected
        $('#message_type').on('change', function() {
            const status = $('#message_type').val();
            
            if (status) {
                // Load existing message - using status as message_type value
                $.get("{{ url('sms/get') }}", {
                    status: status
                }, function(response) {
                    if (response.success && response.sms) {
                        $('#sms_text').val(response.sms);
                        updatePreview();
                    } else {
                        $('#sms_text').val('');
                        updatePreview();
                    }
                });
            } else {
                $('#sms_text').val('');
                updatePreview();
            }
        });

        // Form submission
        $('#sms_form').submit(function(e) {
            e.preventDefault();
            
            const status = $('#message_type').val();
            const smsText = $('#sms_text').val().trim();

            // Validation
            if (!status) {
                show_notification('error', '{{ trans('messages.select_message_type', [], session('locale')) }}');
                return;
            }

            if (!smsText) {
                show_notification('error', '{{ trans('messages.enter_sms_text', [], session('locale')) }}');
                return;
            }

            // Submit form
            $.ajax({
                url: "{{ url('sms') }}",
                method: 'POST',
                data: {
                    status: status,
                    sms: smsText,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        show_notification('success', response.message);
                        // Optionally clear form or keep it
                    } else {
                        show_notification('error', response.message || '{{ trans('messages.generic_error', [], session('locale')) }}');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            show_notification('error', value[0]);
                        });
                    } else {
                        show_notification('error', '{{ trans('messages.generic_error', [], session('locale')) }}');
                    }
                }
            });
        });
    });
</script>

