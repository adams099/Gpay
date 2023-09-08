var expanded = false;
// Toggle checkboxes visibility
function showCheckboxes(checkboxId) {
    var checkboxes = document.getElementById("checkboxes_" + checkboxId);
    if (!expanded) {
        checkboxes.style.display = "block";
        expanded = true;
    } else {
        checkboxes.style.display = "none";
        expanded = false;
    }
}

var dateNow = new Date();
$('#tgl_awal').datetimepicker({
    // format:'Y-m-d 00:00:00',
    format: 'Y-m-d H:i:s',
    formatTime: 'H:i:s',
    defaultTime: '00:00:00',
    timepicker: false
});

$('#tgl_akhir').datetimepicker({
    format: 'Y-m-d H:i:s',
    formatTime: 'H:i:s',
    defaultTime: '23:59:59',
    timepicker: false
});


// func filter sbu berdasarkan merchant container
function updateSbuOptionsFromMerch() {
    const selectedMerchantOptions = [];
    $('.merchant-item').each(function() {
        const isChecked = $(this).find('input').prop('checked');
        if (isChecked) {
            const sbuGroup = $(this).attr('sbu_group').toLowerCase();
            selectedMerchantOptions.push(sbuGroup);
        }
    });

    $('.sbu-filter').each(function() {
        const sbuName = $(this).val().toLowerCase();
        if (selectedMerchantOptions.length === 0 || selectedMerchantOptions.includes(sbuName)) {
            $(this).parent().show();
            $(this).prop('checked', true);
        } else {
            $(this).parent().hide();
            $(this).prop('checked', false);
        }
    });
}

// func filter sbu berdasarkan merchant group
function updateSbuOptionsFromGroup() {
    const selectedMerchGroups = $('.merchGroup-filter:checked').map(function() {
        return $(this).val().toLowerCase();
    }).get();

    const selectedSbuOptions = [];
    $('.merchant-item').each(function() {
        const sbuGroup = $(this).attr('sbu_group').toLowerCase();
        const merchClassGroup = $(this).attr('merchClass_group').toLowerCase();

        if (selectedMerchGroups.includes(merchClassGroup)) {
            selectedSbuOptions.push(sbuGroup);
        }
    });

    $('.sbu-filter').each(function() {
        const sbuName = $(this).val().toLowerCase();
        if (selectedSbuOptions.length === 0 || selectedSbuOptions.includes(sbuName)) {
            $(this).parent().show();
        } else {
            $(this).parent().hide();
        }
    });
}

// func filter store berdasarkan merchant container
function updateStore() {
    const selectedMerchantOptions = [];
    $('.merchant-item').each(function() {
        const isChecked = $(this).find('input').prop('checked');
        if (isChecked) {
            const sbuGroup = $(this).attr('merchid').toLowerCase();
            selectedMerchantOptions.push(sbuGroup);
        }
    });

    $('.store-filter').each(function() {
        const sbuName = $(this).attr('merchant_chk_box').toLowerCase();
        if (selectedMerchantOptions.includes(sbuName)) {
            $(this).parent().show();
            $(this).prop('disabled', false).prop('checked', true);
        } else {
            $(this).parent().hide();
            $(this).prop('disabled', true).prop('checked', false);
        }
    });
}

// Fungsi untuk filter elemen .merchant-item berdasarkan sbu dan group
function filterMerchantItems() {
    const hiddenMerchantIds = [];
    $('.container-checkbox').eq(0).find('.merchant-item').each(function() {
        // const merchantId = $(this).find('input').val();
        const merchantName = $(this).find('label').text().toLowerCase();
        const sbuGroup = $(this).attr('sbu_group').toLowerCase();
        const merchClassGroup = $(this).attr('merchClass_group').toLowerCase();
        const $checkbox = $(this).find('input');

        const sbuFiltersChecked = $('.sbu-filter:checked').map(function() {
            return $(this).val().toLowerCase();
        }).get();

        const merchGroupFiltersChecked = $('.merchGroup-filter:checked').map(function() {
            return $(this).val().toLowerCase();
        }).get();

        if (
            sbuFiltersChecked.includes(sbuGroup) && merchGroupFiltersChecked.includes(merchClassGroup)
        ) {
            $(this).show();
            $checkbox.prop('disabled', false).prop('checked', true);
        } else {
            $(this).hide();
            $checkbox.prop('disabled', true).prop('checked', false);
        }

    });
}

// Fungsi untuk filter elemen .store-item berdasarkan sbu dan group
function filterStoreItems() {
    $('.container-checkbox').eq(1).find('.store-item').each(function() {
        const sbuGroup = $(this).attr('sbu_group').toLowerCase();
        const merchClassGroup = $(this).attr('merchClass_group').toLowerCase();
        const $checkbox = $(this).find('input');

        const sbuFiltersChecked = $('.sbu-filter:checked').map(function() {
            return $(this).val().toLowerCase();
        }).get();

        const merchGroupFiltersChecked = $('.merchGroup-filter:checked').map(function() {
            return $(this).val().toLowerCase();
        }).get();

        if (
            sbuFiltersChecked.includes(sbuGroup) && merchGroupFiltersChecked.includes(merchClassGroup)
        ) {
            $(this).show();
            $checkbox.prop('disabled', false).prop('checked', true);
        } else {
            $(this).hide();
            $checkbox.prop('disabled', true).prop('checked', false);
        }
    });
}

// filterMerchantItems & filterStoreItems on checkbox sbu or merchGroup changed
$('.sbu-filter, .merchGroup-filter').on('change', function() {
    const filterType = $(this).attr('class');
    if (filterType.includes('sbu-filter')) {
        $('#sbu-all').prop("checked", false);
    } else {
        // Check all merchant group checkboxes are checked
        const allMerchantGroupsChecked = $('.merchGroup-filter:checked').length === $('.merchGroup-filter').length;
        $('#merchantGroup').prop("checked", allMerchantGroupsChecked);

        // Clear the value input merchant
        if (!allMerchantGroupsChecked) {
            $('#merchantList').val('');
            if ($('#merchantAll').prop('disabled') && $('#storeAll').prop('disabled')) {
                $('#merchantAll').prop('disabled', false);
                $('#storeAll').prop('disabled', false);
            }
        }

        $('#sbu-all').prop("checked", true);
        $('[name="sbu_filter[]"]').prop("checked", true);
        updateSbuOptionsFromGroup();
        if (!$('#merchantGroup').prop("checked")) {
            $('#merchantList').prop('disabled', true);
        } else {
            $('#merchantList').prop('disabled', false);
        }
        expanded = false;
        showCheckboxes('sbu');
    }
    filterMerchantItems();
    filterStoreItems();
    if (!$('#merchantAll').prop('disabled') && !$('#storeAll').prop('disabled')) {
        $('#merchantAll').prop("checked", true);
        $('#storeAll').prop("checked", true);
    }
});

// Filter by Merchant container
$(".merchant-filter").change(function(event) {
    var merch_id = this.value;
    if (this.checked === true) {
        $("[merchant_group='" + merch_id + "']").toggleClass("d-none");
        $("[merchant_chk_box='" + merch_id + "']").toggleClass("d-none");
        $("[merchant_chk_box='" + merch_id + "']").prop("checked", true)
    } else {
        $("[merchant_group='" + merch_id + "']").toggleClass("d-none");
        $("[merchant_chk_box='" + merch_id + "']").toggleClass("d-none");
        $("[merchant_chk_box='" + merch_id + "']").prop("checked", false)
    }
    updateSbuOptionsFromMerch();
});

// filter input merchant
$("#merchantList").change(function(event) {
    updateSbuOptionsFromMerch();
    expanded = false;
    showCheckboxes('sbu');
});

// filter merchant container berdasarkan input merchant
$('#merchantList').on('input', function() {
    const searchValue = $('#merchantList').val().toLowerCase();
    const $options = $('.merchant-options').eq(0).find('option');

    $options.hide().filter(function() {
        return $(this).val().toLowerCase().includes(searchValue);
    }).show();

    $('.container-checkbox').eq(0).find('.merchant-item').each(function() {
        const merchantName = $(this).find('label').text().toLowerCase();
        const $checkbox = $(this).find('input');

        if (merchantName.includes(searchValue)) {
            $checkbox.prop('disabled', false).prop('checked', true);
            $(this).show();
        } else {
            $checkbox.prop('disabled', true).prop('checked', false);
            $(this).hide();
        }
    });

    if (searchValue !== '') {
        $('#merchantAll').prop('disabled', true).prop("checked", false);
        $('#storeAll').prop('disabled', true).prop("checked", false);
        updateStore();

    } else {
        $('#merchantAll').prop('disabled', false).prop("checked", true);
        $('#storeAll').prop('disabled', false).prop("checked", true);
        $('[name="merchant_filter[]"]').prop("checked", true);
        updateStore();
    }
});

//Select  / unselect all merchant group and all sbu
$('#merchantGroup, #sbu-all').click(function(event) {
    check = this.checked;

    const filterType = $(this).attr('id');
    if (filterType.includes('merchantGroup')) {
        if (check) {
            $('[name="merchGroup_filter[]"]').prop("checked", check);
            $('#merchantList').prop('disabled', false)

        } else {
            $('#merchantList').val('');
            $('#merchantList').prop('disabled', true)
            $('[name="merchGroup_filter[]"]').prop("checked", check);
        }
        filterMerchantItems();
        filterStoreItems();
        $('#sbu-all').prop("checked", true);
        $('[name="sbu_filter[]"]').prop("checked", true);
        updateSbuOptionsFromGroup();
        if (expanded === true) {
            expanded = false;
        }
        showCheckboxes('sbu');
    } else {
        if (check) {
            $('[name="sbu_filter[]"]').prop("checked", check);
        } else {
            $('[name="sbu_filter[]"]').prop("checked", check);
        }
        filterMerchantItems();
        filterStoreItems();

    }
});

//Select  / unselect all merchant, all store, paymentPaid and paymentRefund
$('#merchantAll, #storeAll, #paymentPaid, #paymentRefund').click(function(event) {
    check = this.checked;

    const filterType = $(this).attr('id');
    if (filterType.includes('merchantAll')) {
        if (check) {
            $('[name="merchant_filter[]"]').prop("checked", check);
            $('#storeAll').prop("checked", check);
            $('[name="store_filter[]"]').prop("checked", check);
            $(".store-item").removeClass("d-none");
        } else {
            $('[name="merchant_filter[]"]').prop("checked", check);
            $('#storeAll').prop("checked", check);
            $('[name="store_filter[]"]').prop("checked", check);

            $(".store-item").addClass("d-none");
        }

    } else if (filterType.includes('storeAll')) {
        $('[name="store_filter[]"]').not('.d-none').prop("checked", this.checked);

    } else if (filterType.includes('paymentPaid')) {
        if (!check) {
            if ($('#paymentRefund:checked')) {
                $('#paymentRefund').prop("checked", true);
            }
        }

    } else {
        if (!check) {
            if ($('#paymentPaid:checked')) {
                $('#paymentPaid').prop("checked", true);
            }
        }
    }
});

document.getElementById("btnApply").onclick = function() {
    $.each($("input[name='dateFilter']:checked"), function() {
        var dateFilter = $(this).val();
        var request = new XMLHttpRequest();
        var url = "<?= base_url('transaction_history/submit_search'); ?>";
        request.open('POST', url, true);
        request.send();
    });
};

document.getElementById("btnReset").onclick = function() {
    console.log("reset");
    $('input[type=checkbox]').prop('checked', false);
    window.location.href = "index";
};