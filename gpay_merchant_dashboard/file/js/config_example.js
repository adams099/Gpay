var urlConfig = {
    apiUrl: '',
    baseUrl: window.location.origin + '/gpay-revamp/index.php/',
    kycUrl: window.location.origin + '/gpay-revamp/upload/kyc/'
};

// Modal Confirm
$('#modal_confirm').on('show.bs.modal', function(e) {
    // Ubah form action
    $(this).find('#form_confirm').attr('action', $(e.relatedTarget).data('action'));

    // Ubah title
    let title = $(e.relatedTarget).data('title');
    $(this).find('#title_confirm').text(title);

    // Ubah label
    let label = $(e.relatedTarget).data('label');
    $(this).find('#label_confirm').text(label);
});