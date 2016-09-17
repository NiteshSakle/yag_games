function getHTMLData(url, targetSele){
    return $.ajax({
        url: url,
        type: 'get',        
        dataType: 'html',
        success:function(response) {            
            if ($(targetSele).length && response) {
                $(targetSele).html(response);
            }
        }
    });
}

$(document).ready(function () {
    
    //New Pagination
    $(document).on('click', 'div#sbtFrmPagination button.page-btn', function (e) {
        e.preventDefault();
        var newPageUrl = $(this).data('page-href');
        if (newPageUrl.length) {
            getHTMLData(newPageUrl ,'.media-list');            
        }
    });
    
    $(document).on('keypress', 'div#sbtFrmPagination input.page-input', function (e) {
        evt = (e) ? e : window.event;
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        var maxPages = $(this).data('total-pages');
        var currValue = $(this).val();
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }

        return true;
    });
    
    $(document).on('keyup', 'div#sbtFrmPagination input.page-input', function (e) {
        evt = (e) ? e : window.event;
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        var maxPages = parseInt($(this).data('total-pages'));
        var currPage = parseInt($(this).val());
        if (currPage > maxPages) {
            currPage = maxPages;
            $(this).val(currPage);
        } else if (currPage === 0) {
            currPage = 1;
            $(this).val(currPage);
        }

        if (charCode == 13) {
            var newPageUrl = $(this).data('page-href');
            var pageNumStr = 'inputPageNum';
            if (newPageUrl.length && currPage > 0) {
                if (newPageUrl.indexOf(pageNumStr) !== -1) {
                    newPageUrl = newPageUrl.replace(pageNumStr, currPage);
                    getHTMLData(newPageUrl ,'.media-list');  
                }
            }
        }
    });
});