var wow = new WOW(
        {
            boxClass: 'wowload', // animated element css class (default is wow)
            animateClass: 'animated', // animation css class (default is animated)
            offset: 0, // distance to the element when triggering the animation (default is 0)
            mobile: true, // trigger animations on mobile devices (default is true)
            live: true        // act on asynchronously loaded content (default is true)
        }
);
wow.init();
$(document).ready(function () {
//    if (disableRgtClk) {
//        $(document).bind("contextmenu", function (e) {
//            return false;
//        });
//    }
    
    $('a#headerLoginBtn').on('click', function(e){
        e.preventDefault();
        showModal(baseURL+'/workbox.php?mobileView=true&mode=loginPopup');
    });    
    
    $('button.showModal, a.showModal').on('click', function(e){      
        e.preventDefault();
        showModal($(this).data('href'));
    });
    
    $('#yourIntoContestModal').on('show.bs.modal', function (e) {
        $('span.contest-name').text($(e.relatedTarget).data('contest-name'));
        $('img.down-image').attr('src', $(e.relatedTarget).data('img-src'));
    });
    
    //Pinterest
    $('.btnPinIt').click(function () {
        var url = $(this).attr('href');
        var media = encodeURIComponent($(this).attr('data-image'));
        var desc = $(this).attr('data-desc');
        window.open("//www.pinterest.com/pin/create/button/" +
                "?url=" + url +
                "&media=" + media +
                "&description=" + desc, "pinIt", "toolbar=no, scrollbars=no, resizable=no, top=0, right=0, width=750, height=320");
        return false;
    });

    $('[data-accr-toggle-icons]').on('show.bs.collapse', function (e) {
        $(this).find('.panel-title .fa').removeClass('fa-plus-square-o').addClass('fa-minus-square-o');
    });
    $('[data-accr-toggle-icons]').on('hide.bs.collapse', function (e) {
        $(this).find('.panel-title .fa').removeClass('fa-minus-square-o').addClass('fa-plus-square-o');
    });
});

function headerLogin()
{
    //Header Login Popup  
    $("#header-login-modal").submit(function (e) {
        $('#lm-form-errors').empty();
        $("#header-login-modal").validationEngine('attach', {
            maxErrorsPerField: 1,
            promptPosition: "bottomLeft"
        });
        if ($("#header-login-modal").validationEngine('validate')) {
            var url = baseURL + '/actions.php';
            var data = $("#header-login-modal").serialize();
            $.when(getResponse(url, data)).then(function (data) {                
                switch (data.errorCode)
                {
                    case '0':
                        var lastPart = window.location.href.split("/").pop();
                        goto(baseURL + '/' + lastPart);
                        break;
                    case 'loginsuccess':                        
                        if (!data.is_redirected) {
                            if (data.is_buyer == 'yes') {
                                goto(baseURL + '/buyer.welcome.php');
                            } else {
                                goto(baseURL + '/artist.welcome.php');
                            }
                        } else {
                            login_redirect(data.is_redirected);
                        }
                        break;
                    case 'emailExists':
                    case 'incorrectPassword':
                        showErrorMsg('#lm-form-errors', data.errorMessage);
                        break;
                    case 'loginsuccessCart':
                        goto(baseURL + '/cart.shipping.php');
                        break;
                    case 'loginFailedMessage':
                        if (data.is_buyer == 'yes') {
                            showErrorMsg('#lm-form-errors', 'Login Failed: Email or Password is incorrect.');
                        } else {
                            showErrorMsg('#lm-form-errors', 'Login Failed: Email / Username or Password is incorrect.');
                        }
                        break;
                    case 'loginblank':
                        if (data.is_buyer == 'yes') {
                            showErrorMsg('#lm-form-errors', 'Login Failed: Please enter Email and Password.');
                        } else {
                            showErrorMsg('#lm-form-errors', 'Login Failed: Please enter Email / Username and Password.');
                        }
                        break;
                    case 'loginAccountClosed':
                        showErrorMsg('#lm-form-errors', 'This account is closed or inactive.');
                        break;
                    case 'loginPending':
                        showErrorMsg('#lm-form-errors', 'This account is not verified. It must be verified before you can login.');
                        break;
                    case 'loginsuccessCart':
                        goto(baseURL + '/cart.shipping.php');
                        break;
                }
            });
            return false;
        }
        e.preventDefault();
    });
}
function showErrorMsg(parentSelector, msg)
{
    $(parentSelector).html("<p class='text-danger'>" + msg + "</p>");
}
function getResponse(url, data)
{

    return $.ajax({
        url: url,
        type: 'post',
        data: data,
        dataType: 'json'
    });

}
function login_redirect(page) {
    switch (page) {
        case "cart_shipping":
            goto(baseURL + '/cart.shipping.php');
            break;
        case "contests":
            goto(baseURL + '/contests');
            break;
        case "analytics":
            goto(baseURL + '/analytic.php');
            break;
        case "portfolio":
            goto(baseURL + '/portfolio.php');
            break;
        default:
            goto(baseURL + '/' + page);
            break;
    }
}
function goto(gotolink)
{
    location.href = gotolink;
}

function showModal(url, method)
{
    method = typeof method !== 'undefined' ? method : "get";

    return $.ajax({
        url: url,
        type: method,
        success: function (data) {
            if (data.length) {
                $('div#remoteModal div.modal-content').html(data);
                $('div#remoteModal').modal('show');
            }
        }
    });
}
function submitForm(url, data, method, datatype) {

    data = typeof data !== 'undefined' ? data : "";
    method = typeof method !== 'undefined' ? method : "post";
    datatype = typeof datatype !== 'undefined' ? datatype : "json";

    return $.ajax({
        url: url,
        type: method,
        data: data,
        dataType: datatype,
        beforeSend: function () {
            $("#loaderImgDiv").show();
        },
        complete: function () {
            $("#loaderImgDiv").hide();
        }
    });
}
function returnStarStatus(elem)
{
    $(elem).find('.ratingStar').each(function (key, elem2)
    {
        $(this).attr('src', imgPath + '/star.' + $(this).attr('originalStatus') + '.png');
    });
}
function highlightStars(elem, current)
{
    $(elem).find('.ratingStar').each(function (key, elem2)
    {
        if (current >= key)
            $(elem2).attr('src', imgPath + '/star.1.png');
        else
            $(elem2).attr('src', imgPath + '/star.0.png');
    });
}
$(document).ready(function () {
    $('.navbar-collapse.top-navbar').on('show.bs.collapse', function () {
        $('div#topscroll-txt').hide();
    }).on('hidden.bs.collapse', function () {
        $('div#topscroll-txt').show();
    });

    $('div#remoteModal').on('hide.bs.modal', function (e) {
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
    });

    var starCount = ($(".starRating:first").find('.ratingStar').size());
    $(".starRating").each(function (key, elem)
    {
        $(elem).hover('', function () {
            returnStarStatus(elem);
        });

        $(elem).find('.ratingStar').each(function (key2, elem2)
        {
            var starValue = key2 + 1;
            if (starCount == 5)
                starValue = starValue * 2; // Double the value if there are only 5 stars instead of 10

            var mediaID = $(elem).attr('mediaID'); // Get the media ID these stars are for

            $(elem2)
                    .css('cursor', 'pointer') // Assign a pointer cursor to those stars that are active				
                    .mouseover(function () {
                        highlightStars(elem, key2);
                    }) // Assign a mouse over on the active stars				
                    .click(function ()
                    {
                        $(elem) // Unbind stars to prevent double clicking
                                .find('.ratingStar')
                                .unbind()
                                .css('cursor', 'default');

                        $(elem).unbind().fadeOut(200, function ()
                        {
                            $.get(baseURL + '/actions.php', {action: 'rateMedia', starValue: starValue, mediaID: mediaID, mobileView: true}, function (data)
                            {
                                highlightStars(elem, key2);
                                $(elem).fadeIn();
                            });
                        });

                    }); // Do the rating when the star is clicked on
        });
    });
});