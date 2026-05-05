jQuery(document).ready(function($) {
    $('#form1').on('submit', function(evt) {
        if ($('#gcaptcha').is(':checked') &&
            ($('#gcaptcha_sitekey').val() === '' || $('#gcaptcha_secretkey').val() === '')) {
            evt.preventDefault();
            $('#gcaptcha_error').show();
            scrollToTop();
        }
    });

    function scrollToTop() {
        $('html, body').animate({
            scrollTop: $('#form1').offset().top
        }, 1000);
    }

    function displayInitCheck() {
        $('#adminbar_roles').toggle($('#adminbar').is(':checked'));
        $('#newuserroles').toggle($('#chooserole').is(':checked'));
        $('#urlredirect').toggle($('#automaticlogin').is(':checked'));
        $('#registerredirect_url').toggle($('#registerredirect').is(':checked'));
        $('#loginredirect_url').toggle($('#loginredirect').is(':checked'));
        $('#logoutredirect_url').toggle($('#logoutredirect').is(':checked'));
        $('#emailnotificationcontent').toggle($('#emailnotification').is(':checked'));
        $('#termsconditionsMSG').toggle($('#termsconditions').is(':checked'));
        $('#termsconditionsURL').toggle($('#termsconditions').is(':checked'));
    }

    $('#adminbar').click(function() {
        $('#adminbar_roles').toggle();
    });

    $('#gcaptcha').click(function() {
        if ($(this).is(':checked')) {
            $('#antispam').prop('checked', false).prop('disabled', true);
            $('#gcaptcha_sitekey-label').show();
            $('#gcaptcha_secretkey-label').show();
        } else {
            $('#antispam').prop('disabled', false);
            $('#gcaptcha_sitekey-label').hide();
            $('#gcaptcha_secretkey-label').hide();
        }
    });

    $('#antispam').click(function() {
        if ($(this).is(':checked')) {
            $('#gcaptcha').prop('checked', false).prop('disabled', true);
        } else {
            $('#gcaptcha').prop('disabled', false);
        }
    });

    var selected_roles = cleanLoginAdmin.newuserroles;
    $('select#newuserroles').find('option').each(function() {
        $(this).attr('selected', jQuery.inArray($(this).val(), selected_roles) >= 0);
    });

    $('#chooserole').click(function() {
        $('#newuserroles').toggle();
    });

    $('#automaticlogin').click(function() {
        $('#urlredirect').toggle();
        if ($(this).is(':checked'))
            $('#emailvalidation').prop('checked', false);
    });

    $('#emailvalidation').click(function() {
        if ($(this).is(':checked')) {
            $('#automaticlogin').prop('checked', false);
            $('#urlredirect').hide();
        }
    });

    $('#loginredirect').click(function() {
        $('#loginredirect_url').toggle();
    });

    $('#logoutredirect').click(function() {
        $('#logoutredirect_url').toggle();
    });

    $('#registerredirect').click(function() {
        $('#registerredirect_url').toggle();
    });

    $('#emailnotification').click(function() {
        $('#emailnotificationcontent').toggle($(this).is(':checked'));
    });

    $('#termsconditions').click(function() {
        var checked = $(this).is(':checked');
        $('#termsconditionsMSG').toggle(checked);
        $('#termsconditionsURL').toggle(checked);
    });

    $('#like-donate-more').click(function() {
        $('#like-donate').fadeToggle();
        $('#like-donate-arrow').toggle();
        $('#like-donate-smile').toggle();
    });

    displayInitCheck();
});
