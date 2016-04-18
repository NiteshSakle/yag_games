$(document).ready(function(){
    $('#yourIntoContestModal').on('show.bs.modal', function (e) {
        $('span.contest-name').text($(e.relatedTarget).data('contest-name'));
        $('img.down-image').attr('src', $(e.relatedTarget).data('img-src'));
    });
    
    $('.show-round-btn').on('click', function(){
        var displaySection = $(this).data('show-section');        
        if ($(displaySection).length) {
            $('div#r1Section, div#r2Section, div#r3Section, div#r4Section, div#r5Section, div#r6Section, div#championSection').hide();
            $(displaySection).show();
             $("html, body").animate({scrollTop: $(displaySection).offset().top - 100}, "slow");
        }
    });
    
    $('div.bracket-rankings img.contest-img.pointer').on('click', function(){
        var mediaId = $(this).data('mediaid');
        if (mediaId.length) {
           window.location.href = baseURL+'/photo/'+mediaId+'/photo.html'; 
        }
    });
    
    $('.bracket-rankings').swipe({
        swipeLeft: function () {            
            $('.bracket-rankings').find('.swipe-left:visible').trigger('click');            
        },
        swipeRight: function () {
            $('.bracket-rankings').find('.swipe-right:visible').trigger('click');
        }
    });
    
    $('a.swipe-left, a.swipe-right').on('click', function(){
        if($(this).attr('href').length)  {
            window.location.href = $(this).attr('href');
        }
    });
});